<?php

namespace App\Modules\Archive\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Archive\Models\ArchiveLocation */
class ArchiveLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'parent_id' => $this->parent_id,
            'type' => $this->type?->value ?? $this->type,
            'type_label' => $this->type?->label(),
            'code' => $this->code,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'qr_code' => $this->qr_code,
            'capacity' => $this->capacity,
            'sort_order' => $this->sort_order,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'path' => $this->when(
                $this->relationLoaded('parent') || $this->parent_id === null,
                fn () => $this->pathLabels()
            ),
            'children' => ArchiveLocationResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
