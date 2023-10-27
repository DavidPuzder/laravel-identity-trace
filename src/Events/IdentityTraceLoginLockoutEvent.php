<?php

namespace DavidPuzder\LaravelIdentityTrace\Events;

use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceDevice;
use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceLogin;
use Illuminate\Queue\SerializesModels;

class IdentityTraceLoginLockoutEvent
{
    use SerializesModels;

    public function __construct(
        public IdentityTraceLogin $identityTraceLogin,
        public IdentityTraceDevice $identityTraceDevice
    )
    {

    }
}
