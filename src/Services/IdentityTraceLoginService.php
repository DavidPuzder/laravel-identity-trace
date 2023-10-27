<?php

namespace DavidPuzder\LaravelIdentityTrace\Services;

use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceLoginCreatedEvent;
use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceLoginFailedEvent;
use DavidPuzder\LaravelIdentityTrace\Events\IdentityTraceLoginLockoutEvent;
use DavidPuzder\LaravelIdentityTrace\Interfaces\HasIdentityTraceInterface;
use DavidPuzder\LaravelIdentityTrace\Interfaces\IdentityTraceLoginInterface;
use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceDevice;
use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceLogin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;

class IdentityTraceLoginService {

    /**
     * @param IdentityTraceLoginInterface $traceable
     * @param IdentityTraceDeviceService $deviceService
     * @param array $deviceAgentData
     * @return void
     */
    public function handleLogin(IdentityTraceLoginInterface $traceable, IdentityTraceDeviceService $deviceService, array $deviceAgentData): void
    {
        $identityTraceDevice = $deviceService->findOrCreateTraceableDeviceByAgent($traceable, $deviceAgentData);
        if ($this->shouldLogDeviceLogin($identityTraceDevice)) {
            $identityTraceLogin = $this->createTraceableLoginForDevice($traceable, $identityTraceDevice, $deviceAgentData['ip']);
            if ($identityTraceLogin->type === IdentityTraceLogin::TYPE_LOGIN) {
                event(new IdentityTraceLoginCreatedEvent($identityTraceLogin, $identityTraceDevice));
            }
        }
    }

    /**
     * @param IdentityTraceLoginInterface $traceable
     * @param IdentityTraceDeviceService $deviceService
     * @param array $deviceAgentData
     * @return void
     */
    public function handleFailed(IdentityTraceLoginInterface $traceable, IdentityTraceDeviceService $deviceService, array $deviceAgentData): void
    {
        $identityTraceDevice = $deviceService->findOrCreateTraceableDeviceByAgent($traceable, $deviceAgentData);
        $identityTraceLogin = $this->createTraceableLoginForDevice($traceable, $identityTraceDevice, $deviceAgentData['ip'], IdentityTraceLogin::TYPE_FAILED);
        if ($identityTraceLogin->type === IdentityTraceLogin::TYPE_FAILED) {
            event(new IdentityTraceLoginFailedEvent($identityTraceLogin, $identityTraceDevice));
        }
    }

    /**
     * @param array $payloadData
     * @param IdentityTraceDeviceService $deviceService
     * @param array $deviceAgentData
     * @return void
     */
    public function handleLockout(array $payloadData, IdentityTraceDeviceService $deviceService, array $deviceAgentData): void
    {
        $payload = Collection::make($payloadData);

        /** @var IdentityTraceLoginInterface $traceable */
        $traceable = $this->findTraceableFromPayload($payload);

        if ($traceable) {
            $identityTraceDevice = $deviceService->findOrCreateTraceableDeviceByAgent($traceable, $deviceAgentData);
            $identityTraceLogin = $this->createTraceableLoginForDevice($traceable, $identityTraceDevice, $deviceAgentData['ip'],IdentityTraceLogin::TYPE_LOCKOUT);

            if ($identityTraceLogin->type === IdentityTraceLogin::TYPE_LOCKOUT) {
                event(new IdentityTraceLoginLockoutEvent($identityTraceLogin, $identityTraceDevice));
            }
        }
    }


    /**
     * @param IdentityTraceDevice $identityTraceDevice
     * @return bool
     */
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

    public function createTraceableLoginForDevice(
        IdentityTraceLoginInterface $traceable,
        IdentityTraceDevice $identityTraceDevice,
        string $identityTraceDeviceIp,
        string $type = IdentityTraceLogin::TYPE_LOGIN
    ): IdentityTraceLogin
    {
        $model = IdentityTraceLogin::class;

        $identityTraceLogin = new $model([
            'ip_address' => $identityTraceDeviceIp,
            'device_id' => $identityTraceDevice->id,
            'type' => $type,
        ]);

        $identityTraceLogin->traceable()->associate($traceable);

        $identityTraceDevice->login()->save($identityTraceLogin);

        return $identityTraceLogin;
    }

    /**
     * @return int
     */
    public function getLoginThrottleConfig(): int
    {
        return (int)0;
    }


    /**
     * @param Collection $payload
     * @return IdentityTraceLoginInterface|null
     */
    public function findTraceableFromPayload(Collection $payload): object|null
    {
        $login_column = $this->getLoginColumnConfig();

        if ($payload->has($login_column)) {
            $model = (string)config('auth.providers.users.model');
            $login_value = $payload->get($login_column);

            /** @var Builder $model */
            return $model::where($login_column, '=', $login_value)->first();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getLoginColumnConfig(): string
    {
        return (string)'email';
    }
}
