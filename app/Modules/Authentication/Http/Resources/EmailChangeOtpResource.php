<?php

namespace App\Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Authentication\Models\EmailChangeOtp */
class EmailChangeOtpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'current_email' => $this->current_email,
            'new_email' => $this->new_email,
            'code' => $this->code,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'consumed_at' => $this->consumed_at?->toIso8601String(),
            'attempts' => $this->attempts,
            'status' => $this->statusLabel(),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
