<?php

namespace App\Modules\Workflow\Models;

use App\Core\Traits\HasUuid;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkflowStep extends Model
{
    use HasUuid;

    protected $fillable = [
        'workflow_id',
        'step_order',
        'name',
        'description',
        'role_id',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function approvers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workflow_step_approvers')
            ->withTimestamps();
    }
}
