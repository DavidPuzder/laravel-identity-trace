<?php

namespace DavidPuzder\LaravelIdentityTrace\Events;

use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceDevice;
use Illuminate\Queue\SerializesModels;

class IdentityTraceDeviceCreatedEvent
{
    use SerializesModels;

    public function __construct(
        public IdentityTraceDevice $identityTraceDevice
    )
    {

    }
}
