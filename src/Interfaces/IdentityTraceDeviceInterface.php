<?php

namespace DavidPuzder\LaravelIdentityTrace\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface IdentityTraceDeviceInterface
{
    public function devices(): MorphMany;

    public function hasDevices(): bool;
}
