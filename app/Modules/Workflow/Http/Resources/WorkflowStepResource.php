<?php

namespace App\Modules\Workflow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Workflow\Models\WorkflowStep */
class WorkflowStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'step_order' => $this->step_order,
            'name' => $this->name,
            'description' => $this->description,
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('role', fn () => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ] : null),
            'approvers' => $this->whenLoaded('approvers', fn () => $this->approvers->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])->values()),
            'approver_user_ids' => $this->whenLoaded('approvers', fn () => $this->approvers->pluck('id')->values()),
        ];
    }
}
