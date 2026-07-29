<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(string $organizationId, User $user, int $days = 30): array
    {
        return $this->repository->summary($organizationId, $user, $days);
    }
}
