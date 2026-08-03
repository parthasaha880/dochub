<?php

namespace App\Modules\Documents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Documents\Models\Document */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'folder_id' => $this->folder_id,
            'location_id' => $this->location_id,
            'department_id' => $this->department_id,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'title' => $this->title,
            'reference_no' => $this->reference_no,
            'archive_no' => $this->archive_no,
            'physical_reference' => $this->physical_reference,
            'barcode' => $this->barcode,
            'qr_code' => $this->qr_code,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'confidentiality_level' => $this->confidentiality_level?->value ?? $this->confidentiality_level,
            'document_type' => $this->document_type,
            'version' => $this->version,
            'retention_until' => $this->retention_until?->toDateString(),
            'archive_date' => $this->archive_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'approval_status' => $this->approval_status?->value ?? $this->approval_status,
            'status' => $this->status?->value ?? $this->status,
            'media_type' => $this->media_type ?? 'digital',
            'remarks' => $this->remarks,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'is_locked' => $this->is_locked,
            'is_hidden' => (bool) $this->is_hidden,
            'checked_out_by' => $this->checked_out_by,
            'checked_out_at' => $this->checked_out_at,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
            ]),
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
            ]),
            'folder' => $this->whenLoaded('folder', fn () => [
                'id' => $this->folder?->id,
                'name' => $this->folder?->name,
            ]),
            'location' => $this->whenLoaded('location', fn () => $this->location ? [
                'id' => $this->location->id,
                'type' => $this->location->type?->value ?? $this->location->type,
                'code' => $this->location->code,
                'name' => $this->location->name,
                'path' => method_exists($this->location, 'pathLabels') ? $this->location->pathLabels() : [],
            ] : null),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'relevance' => $this->when(isset($this->relevance), fn () => (float) $this->relevance),
            'versions' => $this->whenLoaded('versions', fn () => $this->versions->map(fn ($v) => [
                'id' => $v->id,
                'version_number' => $v->version_number,
                'original_name' => $v->original_name,
                'size' => $v->size,
                'change_summary' => $v->change_summary,
                'created_at' => $v->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
