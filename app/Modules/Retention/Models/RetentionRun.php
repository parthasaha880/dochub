<?php

namespace App\Modules\Retention\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionRun extends Model
{
    use HasUuid;

    protected $fillable = [
        'organization_id',
        'retention_policy_id',
        'processed',
        'archived',
        'soft_deleted',
        'flagged',
        'status',
        'notes',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'processed' => 'integer',
            'archived' => 'integer',
            'soft_deleted' => 'integer',
            'flagged' => 'integer',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id');
    }
}
