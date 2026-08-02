<?php

namespace App\Modules\Authentication\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeOtp extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'current_email',
        'new_email',
        'code',
        'expires_at',
        'verified_at',
        'consumed_at',
        'attempts',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        if ($this->isConsumed()) {
            return 'used';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'pending';
    }
}
