<?php

namespace App\Modules\Authentication\Policies;

use App\Models\User;
use App\Modules\Authentication\Models\UserDevice;

class UserDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function delete(User $user, UserDevice $device): bool
    {
        return $user->id === $device->user_id;
    }
}
