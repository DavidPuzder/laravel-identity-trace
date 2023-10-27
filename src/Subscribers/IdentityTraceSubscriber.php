<?php

namespace DavidPuzder\LaravelIdentityTrace\Subscribers;

use DavidPuzder\LaravelIdentityTrace\Interfaces\IdentityTraceLoginInterface;
use DavidPuzder\LaravelIdentityTrace\Services\IdentityTraceAgentService;
use DavidPuzder\LaravelIdentityTrace\Services\IdentityTraceDeviceService;
use DavidPuzder\LaravelIdentityTrace\Services\IdentityTraceLoginService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Jenssegers\Agent\Agent;

class IdentityTraceSubscriber {

    /** @var array $deviceAgentData */
    protected array $deviceAgentData;

    /**
     * @param Agent $agent
     * @param IdentityTraceDeviceService $deviceService
     * @param IdentityTraceAgentService $agentService
     * @param IdentityTraceLoginService $identityTraceLoginService
     */
    public function __construct(
        public Agent $agent,
        public IdentityTraceDeviceService $deviceService,
        public IdentityTraceAgentService $agentService,
        public IdentityTraceLoginService $identityTraceLoginService
    )
    {
        $this->deviceAgentData = $agentService->getAgentDataForDevice($agent);
    }

    /**
     * @param $events
     * @return void
     */
    public function subscribe($events): void
    {
        $events->listen(Login::class, [$this, 'onUserLogin']);
        $events->listen(Failed::class, [$this, 'onUserLoginFailed']);
        $events->listen(Lockout::class, [$this, 'onUserLoginLockout']);
    }

    /**
     * @param Login $event
     * @return void
     */
    public function onUserLogin(Login $event): void
    {
        /** @var IdentityTraceLoginInterface $traceable */
        $traceable = $event->user;

        if ($traceable instanceof IdentityTraceLoginInterface) {
            $this->identityTraceLoginService->handleLogin($traceable, $this->deviceService, $this->deviceAgentData);
        }
    }

    /**
     * @param Failed $event
     * @return void
     */
    public function onUserLoginFailed(Failed $event): void
    {
        /** @var IdentityTraceLoginInterface $traceable */
        $traceable = $event->user;

        if ($traceable instanceof IdentityTraceLoginInterface) {
            $this->identityTraceLoginService->handleFailed($traceable, $this->deviceService, $this->deviceAgentData);
        }
    }

    /**
     * @param Lockout $event
     * @return void
     */
    public function onUserLoginLockout(Lockout $event): void
    {
        /** @var IdentityTraceLoginInterface $traceable */
        $payloadData = $event->request->all();

        if (!empty($payloadData)) {
            $this->identityTraceLoginService->handleLockout($payloadData, $this->deviceService, $this->deviceAgentData);
        }
    }

}
