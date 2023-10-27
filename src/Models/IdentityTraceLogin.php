<?php

namespace DavidPuzder\LaravelIdentityTrace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityTraceLogin extends Model
{
    use SoftDeletes, HasUlids;

    /** @var string */
    const TYPE_LOGIN = 'auth';

    /** @var string  */
    const TYPE_FAILED = 'failed';

    /** @var string  */
    const TYPE_LOCKOUT = 'lockout';

    protected $fillable = [
        'traceable_id',
        'traceable_type',
        'ip_address',
        'created_at',
        'type',
    ];

    /**
     * @return MorphTo
     */
    public function traceable(): MorphTo
    {
        return $this->morphTo('traceable');
    }

    /**
     * @return BelongsTo
     */
    public function device(): BelongsTo
    {
        $model = IdentityTraceDevice::class;

        return $this->belongsTo($model);
    }
}
