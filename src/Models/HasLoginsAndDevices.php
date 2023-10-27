<?php

namespace DavidPuzder\LaravelIdentityTrace\Models;

use \Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLoginsAndDevices
{
    /**
     * @return MorphMany
     */
    public function logins(): MorphMany
    {
        $model = IdentityTraceLogin::class;

        return $this->morphMany($model, 'traceable');
    }

    public function auths(): MorphMany
    {
        $relation = $this->logins();
        $relation->where('type', IdentityTraceLogin::TYPE_LOGIN);

        return $relation;
    }

    public function fails(): MorphMany
    {
        $relation = $this->logins();
        $relation->where('type', IdentityTraceLogin::TYPE_FAILED);

        return $relation;
    }

    public function lockouts(): MorphMany
    {
        $relation = $this->logins();
        $relation->where('type', IdentityTraceLogin::TYPE_LOCKOUT);

        return $relation;
    }

    public function devices(): MorphMany
    {
        $model = IdentityTraceDevice::class;

        return $this->morphMany($model, 'traceable');
    }

    public function hasDevices(): bool
    {
        return $this
            ->devices()
            ->select('id')
            ->get()
            ->isNotEmpty();
    }

}
