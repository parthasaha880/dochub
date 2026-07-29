<?php

namespace App\Modules\Workflow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Workflow\Models\WorkflowInstance */
class WorkflowInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'document_id' => $this->document_id,
            'workflow_id' => $this->workflow_id,
            'current_step_id' => $this->current_step_id,
            'status' => $this->status?->value ?? $this->status,
            'submitted_by' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'submission_note' => $this->submission_note,
            'document' => $this->whenLoaded('document', fn () => $this->document ? [
                'id' => $this->document->id,
                'title' => $this->document->title,
                'reference_no' => $this->document->reference_no,
                'approval_status' => $this->document->approval_status?->value,
            ] : null),
            'workflow' => $this->whenLoaded('workflow', fn () => $this->workflow ? [
                'id' => $this->workflow->id,
                'name' => $this->workflow->name,
                'code' => $this->workflow->code,
            ] : null),
            'current_step' => new WorkflowStepResource($this->whenLoaded('currentStep')),
            'submitter' => $this->whenLoaded('submitter', fn () => $this->submitter ? [
                'id' => $this->submitter->id,
                'name' => $this->submitter->name,
                'email' => $this->submitter->email,
            ] : null),
            'actions' => WorkflowActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
