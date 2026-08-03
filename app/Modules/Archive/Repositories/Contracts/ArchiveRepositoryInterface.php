<?php

namespace App\Modules\Archive\Repositories\Contracts;

use App\Modules\Archive\Models\ArchiveLocation;
use Illuminate\Support\Collection;

interface ArchiveRepositoryInterface
{
    public function tree(string $organizationId, ?string $type = null): Collection;

    public function findLocation(string $id): ArchiveLocation;

    public function createLocation(array $attributes): ArchiveLocation;

    public function updateLocation(ArchiveLocation $location, array $attributes): ArchiveLocation;

    public function deleteLocation(ArchiveLocation $location): void;

    public function findByCodeOrScan(string $organizationId, string $query): ?ArchiveLocation;
}
