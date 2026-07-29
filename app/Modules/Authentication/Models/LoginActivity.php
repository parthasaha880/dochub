<?php

namespace App\Modules\Authentication\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Authentication\Enums\LoginStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'email',
        'status',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'device_name',
        'location',
        'failure_reason',
        'logged_in_at',
        'logged_out_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoginStatus::class,
            'logged_in_at' => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
