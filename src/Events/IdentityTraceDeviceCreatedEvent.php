<?php

namespace DavidPuzder\LaravelIdentityTrace\Events;

use DavidPuzder\LaravelIdentityTrace\Models\IdentityTraceDevice;
use Illuminate\Queue\SerializesModels;

class IdentityTraceDeviceCreatedEvent
{
    use SerializesModels;

    /** @var IdentityTraceDevice $identityTraceDevice */
    public IdentityTraceDevice $identityTraceDevice;

    public function __construct(IdentityTraceDevice $identityTraceDevice)
    {
        $this->identityTraceDevice = $identityTraceDevice;
    }
}
