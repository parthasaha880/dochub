<?php

namespace App\Modules\Workflow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Workflow\Models\WorkflowAction */
class WorkflowActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_instance_id' => $this->workflow_instance_id,
            'workflow_step_id' => $this->workflow_step_id,
            'action' => $this->action?->value ?? $this->action,
            'comments' => $this->comments,
            'meta' => $this->meta,
            'acted_at' => $this->acted_at?->toIso8601String(),
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'email' => $this->actor->email,
            ] : null),
            'step' => $this->whenLoaded('step', fn () => $this->step ? [
                'id' => $this->step->id,
                'name' => $this->step->name,
                'step_order' => $this->step->step_order,
            ] : null),
            'document' => $this->when(
                $this->relationLoaded('instance') && $this->instance?->relationLoaded('document'),
                fn () => $this->instance->document ? [
                    'id' => $this->instance->document->id,
                    'title' => $this->instance->document->title,
                ] : null
            ),
        ];
    }
}
