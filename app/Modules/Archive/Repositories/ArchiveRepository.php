<?php

namespace App\Modules\Archive\Repositories;

use App\Modules\Archive\Models\ArchiveLocation;
use App\Modules\Archive\Repositories\Contracts\ArchiveRepositoryInterface;
use Illuminate\Support\Collection;

class ArchiveRepository implements ArchiveRepositoryInterface
{
    public function tree(string $organizationId, ?string $type = null): Collection
    {
        $roots = ArchiveLocation::query()
            ->with(['children.children.children.children'])
            ->where('organization_id', $organizationId)
            ->whereNull('parent_id')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return $roots;
    }

    public function findLocation(string $id): ArchiveLocation
    {
        return ArchiveLocation::query()
            ->with(['parent.parent.parent.parent', 'children', 'organization:id,name,code'])
            ->findOrFail($id);
    }

    public function createLocation(array $attributes): ArchiveLocation
    {
        return ArchiveLocation::query()->create($attributes);
    }

    public function updateLocation(ArchiveLocation $location, array $attributes): ArchiveLocation
    {
        $location->update($attributes);

        return $location->refresh();
    }

    public function deleteLocation(ArchiveLocation $location): void
    {
        $location->delete();
    }

    public function findByCodeOrScan(string $organizationId, string $query): ?ArchiveLocation
    {
        $query = trim($query);

        return ArchiveLocation::query()
            ->with(['parent.parent.parent.parent'])
            ->where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('code', $query)
                    ->orWhere('barcode', $query)
                    ->orWhere('qr_code', $query);
            })
            ->first();
    }
}
