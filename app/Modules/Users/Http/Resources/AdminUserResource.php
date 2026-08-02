<?php

namespace App\Modules\Users\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'employee_id' => $this->employee_id,
            'is_active' => $this->is_active,
            'force_password_change' => $this->force_password_change,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'last_login_ip' => $this->last_login_ip,
            'locked_until' => $this->locked_until,
            'is_locked' => $this->isLocked(),
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'theme' => $this->theme,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'hierarchy_level' => $role->hierarchy_level,
            ])),
            'role_names' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')),
            'direct_permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
