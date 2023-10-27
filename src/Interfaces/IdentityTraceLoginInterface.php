<?php

namespace DavidPuzder\LaravelIdentityTrace\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface IdentityTraceLoginInterface
{
    public function logins(): MorphMany;

    public function auths(): MorphMany;

    public function fails(): MorphMany;

    public function lockouts(): MorphMany;
}
