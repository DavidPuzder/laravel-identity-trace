<?php

namespace DavidPuzder\LaravelIdentityTrace\Subscribers;

use DavidPuzder\LaravelIdentityTrace\Interfaces\HasIdentityTraceInterface;
use DavidPuzder\LaravelIdentityTrace\Services\IdentityTraceService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;

class IdentityTraceSubscriber {


    public function subscribe($events): void
    {
        $events->listen(Login::class, [$this, 'onUserLogin']);
        $events->listen(Failed::class, [$this, 'onUserLoginFailed']);
        $events->listen(Lockout::class, [$this, 'onUserLoginLockout']);
    }

    public function onUserLogin(Login $event): void
    {
        /** @var HasIdentityTraceInterface $traceable */
        $traceable = $event->user;

        if ($traceable instanceof HasIdentityTraceInterface) {

            /** @var IdentityTraceService $manager */
            $manager = app('identity-trace-service');
            $manager->handleLogin($traceable);
        }
    }

    public function onUserLoginFailed(Failed $event): void
    {
        /** @var HasIdentityTraceInterface $traceable */
        $traceable = $event->user;

        if ($traceable instanceof HasIdentityTraceInterface) {

            /** @var IdentityTraceService $manager */
            $manager = app('identity-trace-service');
            $manager->handleFailed($traceable);
        }
    }

    public function onUserLoginLockout(Lockout $event): void
    {
        $payload = $event->request->all();

        if (!empty($payload)) {
            /** @var IdentityTraceService $manager */
            $manager = app('identity-trace-service');
            $manager->handleLockout($payload);
        }
    }

}
