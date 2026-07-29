<?php

namespace App\Modules\Authentication\Repositories\Contracts;

use App\Models\User;
use App\Modules\Authentication\Models\LoginActivity;
use App\Modules\Authentication\Models\UserDevice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuthRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function createLoginActivity(array $attributes): LoginActivity;

    public function upsertDevice(User $user, array $attributes): UserDevice;

    public function paginateLoginActivities(User $user, int $perPage = 15): LengthAwarePaginator;

    public function paginateDevices(User $user, int $perPage = 15): LengthAwarePaginator;

    public function findDeviceForUser(User $user, string $deviceId): ?UserDevice;

    public function revokeDevice(UserDevice $device): void;

    public function revokeOtherDevices(User $user, ?string $exceptFingerprint = null): int;
}
