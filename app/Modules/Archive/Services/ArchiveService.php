<?php

namespace App\Modules\Archive\Services;

use App\Models\User;
use App\Modules\Archive\Enums\LocationType;
use App\Modules\Archive\Enums\MediaType;
use App\Modules\Archive\Models\ArchiveLocation;
use App\Modules\Archive\Repositories\Contracts\ArchiveRepositoryInterface;
use App\Modules\Documents\Enums\DocumentStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentCategory;
use App\Modules\Documents\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArchiveService
{
    public function __construct(
        private readonly ArchiveRepositoryInterface $locations,
        private readonly DocumentRepositoryInterface $documents,
        private readonly DocumentNumberingService $numbering,
    ) {}

    public function locationTree(string $organizationId): Collection
    {
        return $this->locations->tree($organizationId);
    }

    public function showLocation(string $id): ArchiveLocation
    {
        return $this->locations->findLocation($id);
    }

    public function createLocation(array $data, User $actor): ArchiveLocation
    {
        $type = LocationType::from($data['type']);
        $parent = null;

        if (! empty($data['parent_id'])) {
            $parent = $this->locations->findLocation($data['parent_id']);
            $this->assertChildType($parent, $type);
            $data['organization_id'] = $parent->organization_id;
        } elseif ($type !== LocationType::Room) {
            throw ValidationException::withMessages([
                'parent_id' => ['Only rooms can be created without a parent. Hierarchy is Room → Rack → Shelf → Box → File.'],
            ]);
        }

        if (empty($data['organization_id'])) {
            throw ValidationException::withMessages([
                'organization_id' => ['Organization is required.'],
            ]);
        }

        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        if ($code === '') {
            $code = $this->suggestLocationCode($data['organization_id'], $type);
        }

        return $this->locations->createLocation([
            'organization_id' => $data['organization_id'],
            'parent_id' => $parent?->id,
            'type' => $type->value,
            'code' => $code,
            'name' => $data['name'],
            'barcode' => $data['barcode'] ?? $this->scanCode('LOC'),
            'qr_code' => $data['qr_code'] ?? $this->scanCode('LQR'),
            'capacity' => $data['capacity'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'description' => $data['description'] ?? null,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    public function updateLocation(string $id, array $data, User $actor): ArchiveLocation
    {
        $location = $this->locations->findLocation($id);

        if (isset($data['type']) && $data['type'] !== $location->type->value) {
            throw ValidationException::withMessages([
                'type' => ['Location type cannot be changed after creation.'],
            ]);
        }

        unset($data['type'], $data['organization_id'], $data['parent_id']);

        $data['updated_by'] = $actor->id;

        return $this->locations->updateLocation($location, $data);
    }

    public function deleteLocation(string $id, User $actor): void
    {
        $location = $this->locations->findLocation($id);

        if ($location->children()->exists()) {
            throw ValidationException::withMessages([
                'location' => ['Remove child locations first.'],
            ]);
        }

        if ($location->documents()->exists()) {
            throw ValidationException::withMessages([
                'location' => ['This location still has linked documents.'],
            ]);
        }

        $location->forceFill([
            'deleted_by' => $actor->id,
            'code' => $location->code.'-DEL-'.Str::upper(Str::random(4)),
        ])->save();
        $this->locations->deleteLocation($location);
    }

    public function categoryTree(string $organizationId): Collection
    {
        return DocumentCategory::query()
            ->with('children')
            ->where('organization_id', $organizationId)
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();
    }

    public function createCategory(array $data, User $actor): DocumentCategory
    {
        return DocumentCategory::query()->create([
            'organization_id' => $data['organization_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'code' => strtoupper(trim($data['code'])),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    public function updateCategory(string $id, array $data, User $actor): DocumentCategory
    {
        $category = DocumentCategory::query()->findOrFail($id);
        $category->update([
            ...collect($data)->only(['code', 'name', 'description', 'is_active', 'parent_id'])->all(),
            'updated_by' => $actor->id,
        ]);

        return $category->fresh()->load('children');
    }

    public function deleteCategory(string $id): void
    {
        $category = DocumentCategory::query()->findOrFail($id);

        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Remove child classifications first.'],
            ]);
        }

        if (Document::query()->where('category_id', $id)->orWhere('subcategory_id', $id)->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Classification is in use by documents.'],
            ]);
        }

        $category->delete();
    }

    public function paginateArchive(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = $filters['status'] ?? DocumentStatus::Archived->value;

        return $this->documents->paginateDocuments($filters, $perPage);
    }

    public function paginateHybrid(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $filters['media_type'] = $filters['media_type'] ?? MediaType::Hybrid->value;

        return $this->documents->paginateDocuments($filters, $perPage);
    }

    public function paginatePhysical(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $filters['media_type'] = MediaType::Physical->value;

        return $this->documents->paginateDocuments($filters, $perPage);
    }

    /**
     * Track by barcode / QR / archive_no / reference / location code.
     *
     * @return array{type: string, location?: ArchiveLocation, document?: Document}
     */
    public function lookup(string $organizationId, string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            throw ValidationException::withMessages([
                'query' => ['Enter a barcode, QR code, archive number, or location code.'],
            ]);
        }

        $location = $this->locations->findByCodeOrScan($organizationId, $query);
        if ($location) {
            return [
                'type' => 'location',
                'location' => $location,
                'path' => $location->pathLabels(),
            ];
        }

        $document = Document::query()
            ->with(['folder', 'location.parent.parent.parent.parent', 'category', 'tags'])
            ->where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('barcode', $query)
                    ->orWhere('qr_code', $query)
                    ->orWhere('archive_no', $query)
                    ->orWhere('reference_no', $query)
                    ->orWhere('physical_reference', $query);
            })
            ->first();

        if ($document) {
            return [
                'type' => 'document',
                'document' => $document,
                'location_path' => $document->location?->pathLabels() ?? [],
            ];
        }

        throw ValidationException::withMessages([
            'query' => ['No archive record matched that code.'],
        ]);
    }

    public function assignDocumentLocation(string $documentId, ?string $locationId, ?string $mediaType, User $actor): Document
    {
        $document = $this->documents->findDocument($documentId);

        if ($locationId) {
            $location = $this->locations->findLocation($locationId);
            if ($location->organization_id !== $document->organization_id) {
                throw ValidationException::withMessages([
                    'location_id' => ['Location belongs to another organization.'],
                ]);
            }
            if (! in_array($location->type, [LocationType::Box, LocationType::File], true)) {
                throw ValidationException::withMessages([
                    'location_id' => ['Documents should be assigned to a Box or File location.'],
                ]);
            }
        }

        $payload = [
            'location_id' => $locationId,
            'updated_by' => $actor->id,
        ];

        if ($mediaType) {
            $payload['media_type'] = MediaType::from($mediaType)->value;
        } elseif ($locationId && $document->path) {
            $payload['media_type'] = MediaType::Hybrid->value;
        } elseif ($locationId && ! $document->path) {
            $payload['media_type'] = MediaType::Physical->value;
        }

        if ($locationId && blank($document->archive_no)) {
            $payload['archive_no'] = $this->numbering->next($document->organization_id, 'ARC');
        }

        if ($locationId && blank($document->physical_reference) && isset($location)) {
            $payload['physical_reference'] = implode(' / ', $location->pathLabels());
        }

        return $this->documents->updateDocument($document, $payload)->load(['location.parent.parent.parent.parent', 'folder', 'tags']);
    }

    public function archiveDocument(string $documentId, User $actor, ?string $locationId = null): Document
    {
        return DB::transaction(function () use ($documentId, $actor, $locationId) {
            $document = $this->documents->findDocument($documentId);

            $payload = [
                'status' => DocumentStatus::Archived->value,
                'archive_date' => now()->toDateString(),
                'updated_by' => $actor->id,
            ];

            if (blank($document->archive_no)) {
                $payload['archive_no'] = $this->numbering->next($document->organization_id, 'ARC');
            }

            $document = $this->documents->updateDocument($document, $payload);

            if ($locationId) {
                $document = $this->assignDocumentLocation($document->id, $locationId, null, $actor);
            }

            return $document->load(['location.parent.parent.parent.parent', 'folder', 'tags', 'category']);
        });
    }

    public function stats(string $organizationId): array
    {
        $base = Document::query()->where('organization_id', $organizationId);

        return [
            'digital' => (clone $base)->where('media_type', MediaType::Digital->value)->count(),
            'physical' => (clone $base)->where('media_type', MediaType::Physical->value)->count(),
            'hybrid' => (clone $base)->where('media_type', MediaType::Hybrid->value)->count(),
            'archived' => (clone $base)->where('status', DocumentStatus::Archived->value)->count(),
            'locations' => ArchiveLocation::query()->where('organization_id', $organizationId)->count(),
            'rooms' => ArchiveLocation::query()->where('organization_id', $organizationId)->where('type', LocationType::Room->value)->count(),
            'boxes' => ArchiveLocation::query()->where('organization_id', $organizationId)->where('type', LocationType::Box->value)->count(),
            'categories' => DocumentCategory::query()->where('organization_id', $organizationId)->count(),
        ];
    }

    private function assertChildType(ArchiveLocation $parent, LocationType $child): void
    {
        $expected = $parent->type->childType();
        if ($expected === null || $expected !== $child) {
            throw ValidationException::withMessages([
                'type' => [
                    $expected
                        ? 'Under '.$parent->type->label().' you can only create a '.$expected->label().'.'
                        : 'This location type cannot have children.',
                ],
            ]);
        }
    }

    private function suggestLocationCode(string $organizationId, LocationType $type): string
    {
        $prefix = strtoupper(substr($type->value, 0, 1));
        $count = ArchiveLocation::query()
            ->where('organization_id', $organizationId)
            ->where('type', $type->value)
            ->withTrashed()
            ->count() + 1;

        return sprintf('%s-%03d', $prefix, $count);
    }

    private function scanCode(string $prefix): string
    {
        return $prefix.'-'.strtoupper(Str::random(10));
    }
}
