<?php

namespace App\Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Authentication\Models\LoginActivity */
class LoginActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'status' => $this->status?->value ?? $this->status,
            'ip_address' => $this->ip_address,
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'platform' => $this->platform,
            'device_name' => $this->device_name,
            'location' => $this->location,
            'failure_reason' => $this->failure_reason,
            'logged_in_at' => $this->logged_in_at,
            'logged_out_at' => $this->logged_out_at,
            'created_at' => $this->created_at,
        ];
    }
}
