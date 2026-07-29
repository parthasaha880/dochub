<?php

namespace App\Modules\Workflow\Models;

use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Workflow\Enums\WorkflowActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    use HasUuid;

    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'action',
        'actor_id',
        'comments',
        'meta',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => WorkflowActionType::class,
            'meta' => 'array',
            'acted_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
