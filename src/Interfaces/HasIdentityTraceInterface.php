<?php

namespace DavidPuzder\LaravelIdentityTrace\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasIdentityTraceInterface
{
    public function logins(): MorphMany;

    public function auths(): MorphMany;

    public function fails(): MorphMany;

    public function lockouts(): MorphMany;

    public function devices(): MorphMany;

    public function hasDevices(): bool;
}
