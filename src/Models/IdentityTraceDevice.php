<?php

namespace DavidPuzder\LaravelIdentityTrace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IdentityTraceDevice extends Model
{
    use SoftDeletes, HasUlids;

    /** @var array $casts */
    protected $casts = [
        'is_locked' => 'boolean',
        'is_desktop' => 'boolean',
        'is_phone' => 'boolean',
    ];

    /**
     * @return HasMany
     */
    public function logins(): HasMany
    {
        $model = IdentityTraceLogin::class;

        return $this->hasMany($model, 'device_id');
    }

    /**
     * @return HasOne
     */
    public function login(): HasOne
    {
        $model = IdentityTraceLogin::class;

        $relation = $this->hasOne($model, 'device_id');
        $relation->orderBy('created_at', 'desc');
        return $relation;
    }

    /**
     * @return MorphTo
     */
    public function traceable(): MorphTo
    {
        return $this->morphTo();
    }

}
