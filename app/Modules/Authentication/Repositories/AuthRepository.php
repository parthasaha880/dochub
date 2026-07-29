<?php

namespace App\Modules\Authentication\Repositories;

use App\Models\User;
use App\Modules\Authentication\Models\LoginActivity;
use App\Modules\Authentication\Models\UserDevice;
use App\Modules\Authentication\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuthRepository implements AuthRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', strtolower($email))
            ->first();
    }

    public function createLoginActivity(array $attributes): LoginActivity
    {
        return LoginActivity::query()->create($attributes);
    }

    public function upsertDevice(User $user, array $attributes): UserDevice
    {
        return UserDevice::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_fingerprint' => $attributes['device_fingerprint'],
            ],
            [
                'device_name' => $attributes['device_name'] ?? null,
                'device_type' => $attributes['device_type'] ?? null,
                'browser' => $attributes['browser'] ?? null,
                'platform' => $attributes['platform'] ?? null,
                'ip_address' => $attributes['ip_address'] ?? null,
                'last_used_at' => now(),
                'revoked_at' => null,
            ]
        );
    }

    public function paginateLoginActivities(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->loginActivities()
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function paginateDevices(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->devices()
            ->whereNull('revoked_at')
            ->latest('last_used_at')
            ->paginate($perPage);
    }

    public function findDeviceForUser(User $user, string $deviceId): ?UserDevice
    {
        return $user->devices()
            ->whereKey($deviceId)
            ->first();
    }

    public function revokeDevice(UserDevice $device): void
    {
        $device->forceFill(['revoked_at' => now()])->save();
    }

    public function revokeOtherDevices(User $user, ?string $exceptFingerprint = null): int
    {
        $query = $user->devices()->whereNull('revoked_at');

        if ($exceptFingerprint) {
            $query->where('device_fingerprint', '!=', $exceptFingerprint);
        }

        return $query->update(['revoked_at' => now()]);
    }
}
