<?php

namespace App\Modules\Workflow\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Organization\Models\Organization;
use App\Modules\Workflow\Enums\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use Auditable;
    use HasUuid;

    protected $fillable = [
        'organization_id',
        'document_id',
        'workflow_id',
        'current_step_id',
        'status',
        'submitted_by',
        'submitted_at',
        'completed_at',
        'submission_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowInstanceStatus::class,
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('acted_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            WorkflowInstanceStatus::InProgress,
            WorkflowInstanceStatus::Returned,
        ], true);
    }
}
