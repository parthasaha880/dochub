<?php

namespace App\Modules\Dashboard\Repositories\Contracts;

use App\Models\User;

interface DashboardRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function summary(string $organizationId, User $user, int $days = 30): array;
}
