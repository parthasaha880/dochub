<?php

namespace App\Modules\Documents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Documents\Models\Folder */
class FolderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'parent_id' => $this->parent_id,
            'department_id' => $this->department_id,
            'name' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_favorite' => $this->is_favorite,
            'sort_order' => $this->sort_order,
            'description' => $this->description,
            'children' => FolderResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
