<?php

namespace App\Modules\Sharing\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DocumentShare extends Model
{
    use HasUuid;

    protected $fillable = [
        'organization_id',
        'document_id',
        'created_by',
        'token',
        'share_type',
        'label',
        'password_hash',
        'expires_at',
        'max_downloads',
        'download_count',
        'allow_download',
        'is_active',
        'last_accessed_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'allow_download' => 'boolean',
            'is_active' => 'boolean',
            'max_downloads' => 'integer',
            'download_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentShare $share): void {
            if (empty($share->token)) {
                $share->token = Str::random(48);
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_downloads !== null && $this->download_count >= $this->max_downloads;
    }

    public function isAccessible(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isExhausted();
    }
}
