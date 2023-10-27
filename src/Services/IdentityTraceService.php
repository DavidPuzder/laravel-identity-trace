<?php

namespace DavidPuzder\LaravelIdentityTrace\Services;

use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceDeviceCreatedEvent;
use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceLoginCreatedEvent;
use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceLoginFailedEvent;
use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceLoginLockoutEvent;
use DavidPuzder\LaravelIdentityTrace\Interfaces\HasIdentityTraceInterface;
use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceDevice;
use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceLogin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;

class IdentityTraceService {

    /** @var Application $app */
    private $app;
    /** @var Request $request */
    private $request;
    /** @var Config $config */
    private $config;

    public function __construct(Application $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
        $this->config = $app['config'];
    }

    public function handleLogin(HasIdentityTraceInterface $traceable): void
    {
        $identityTraceDevice = $this->findOrCreateTraceableDeviceByAgent($traceable);

        if ($this->shouldLogDeviceLogin($identityTraceDevice)) {
            $this->createTraceableLoginForDevice($traceable, $identityTraceDevice);
        }
    }

    public function handleFailed(HasIdentityTraceInterface $traceable): void
    {
        $identityTraceDevice = $this->findOrCreateTraceableDeviceByAgent($traceable);
        $this->createTraceableLoginForDevice($traceable, $identityTraceDevice, IdentityTraceLogin::TYPE_FAILED);

        event(new IdentityTraceLoginFailedEvent($identityTraceDevice->login, $identityTraceDevice));
    }

    public function handleLockout(array $payload = []): void
    {
        $payload = Collection::make($payload);

        $traceable = $this->findTraceableFromPayload($payload);

        if ($traceable) {
            $identityTraceDevice = $this->findOrCreateTraceableDeviceByAgent($traceable);
            $this->createTraceableLoginForDevice($traceable, $identityTraceDevice, IdentityTraceLogin::TYPE_LOCKOUT);

            event(new IdentityTraceLoginLockoutEvent($identityTraceDevice->login, $identityTraceDevice));
        }
    }

    public function findOrCreateTraceableDeviceByAgent(HasIdentityTraceInterface $traceable, Agent $agent = null): IdentityTraceDevice
    {
        $agent = is_null($agent) ? $this->app['agent'] : $agent;
        $identityTraceDevice = $this->findTraceableDeviceByAgent($traceable, $agent);

        if (is_null($identityTraceDevice)) {
            $identityTraceDevice = $this->createTraceableDeviceByAgent($traceable, $agent);
        }

        return $identityTraceDevice;
    }

    public function findTraceableDeviceByAgent(HasIdentityTraceInterface $traceable, Agent $agent): ?IdentityTraceDevice
    {
        if (! $traceable->hasDevices()) {
            return null;
        }

        return $traceable
            ->devices()
            ->with('login')
            ->get()
            ->filter(fn (IdentityTraceDevice $identityTraceDevice) => $this->deviceMatch($identityTraceDevice, $agent))
            ->first();
    }

    public function createTraceableDeviceByAgent(HasIdentityTraceInterface $traceable, Agent $agent): IdentityTraceDevice
    {
        $model = IdentityTraceDevice::class;
        $identityTraceDevice = new $model;

        $identityTraceDevice->platform = $agent->platform();
        $identityTraceDevice->platform_version = $agent->version($identityTraceDevice->platform);
        $identityTraceDevice->browser = $agent->browser();
        $identityTraceDevice->browser_version = $agent->version($identityTraceDevice->browser);
        $identityTraceDevice->is_desktop = $agent->isDesktop();
        $identityTraceDevice->is_mobile = $agent->isMobile();
        $identityTraceDevice->is_tablet = $agent->isTablet();
        $identityTraceDevice->language = count($agent->languages()) ? $agent->languages()[0] : null;

        $identityTraceDevice->traceable()->associate($traceable);

        $identityTraceDevice->save();

        event(new IdentityTraceDeviceCreatedEvent($identityTraceDevice));

        return $identityTraceDevice;
    }

    public function findTraceableFromPayload(Collection $payload): ?HasIdentityTraceInterface
    {
        $login_column = $this->getLoginColumnConfig();

        if ($payload->has($login_column)) {
            $model = (string)$this->config->get('auth.providers.users.model');
            $login_value = $payload->get($login_column);

            /** @var Builder $model */
            $user = $model::where($login_column, '=', $login_value)->first();
            return $user;
        }

        return null;
    }

    public function createTraceableLoginForDevice(
        HasIdentityTraceInterface $traceable,
        IdentityTraceDevice $identityTraceDevice,
        string $type = IdentityTraceLogin::TYPE_LOGIN
    ): IdentityTraceLogin
    {
        $model = IdentityTraceLogin::class;
        $ip = $this->request->ip();

        $identityTraceLogin = new $model([
            'ip_address' => $ip,
            'device_id' => $identityTraceDevice->id,
            'type' => $type,
        ]);

        $identityTraceLogin->traceable()->associate($traceable);

        $identityTraceDevice->login()->save($identityTraceLogin);

        event(new IdentityTraceLoginCreatedEvent($identityTraceLogin, $identityTraceDevice));

        return $identityTraceLogin;
    }

    public function shouldLogDeviceLogin(IdentityTraceDevice $identityTraceDevice): bool
    {
        $throttle = $this->getLoginThrottleConfig();

        $identityTraceDevice->loadMissing('login');

        if ($throttle === 0 || is_null($identityTraceDevice->login)) {
            return true;
        }

        $limit = Carbon::now()->subMinutes($throttle);
        $identityTraceLogin = $identityTraceDevice->login;

        if (isset($identityTraceLogin->created_at) && $identityTraceLogin->created_at->gt($limit)) {
            return false;
        }

        return true;
    }

    public function deviceMatch(IdentityTraceDevice $identityTraceDevice, Agent $agent, array $attributes = null): bool
    {
        $attributes = is_null($attributes) ? $this->getDeviceMatchingAttributesConfig() : $attributes;
        $matches = 0;

        if (in_array('platform', $attributes)) {
            $matches += $identityTraceDevice->platform === $agent->platform();
        }

        if (in_array('platform_version', $attributes)) {
            $agentPlatformVersion = $agent->version($identityTraceDevice->platform);
            $agentPlatformVersion = empty($agentPlatformVersion) ? '0' : $agentPlatformVersion;
            $matches += $identityTraceDevice->platform_version === $agentPlatformVersion;
        }

        if (in_array('browser', $attributes)) {
            $matches += $identityTraceDevice->browser === $agent->browser();
        }

        if (in_array('browser_version', $attributes)) {
            $matches += $identityTraceDevice->browser_version === $agent->version($identityTraceDevice->browser);
        }

        if (in_array('language', $attributes)) {
            $matches += $identityTraceDevice->language === $agent->version($identityTraceDevice->language);
        }

        return $matches === count($attributes);
    }

    public function getDeviceMatchingAttributesConfig(): array
    {
        return [
            'platform',
            'platform_version',
            'browser',
        ];
    }

    public function getLoginThrottleConfig(): int
    {
        return (int)0;
    }

    public function getLoginColumnConfig(): string
    {
        return (string)'email';
    }
}
