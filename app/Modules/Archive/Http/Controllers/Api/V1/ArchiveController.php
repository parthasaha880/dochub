<?php

namespace App\Modules\Archive\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Archive\Http\Requests\StoreArchiveLocationRequest;
use App\Modules\Archive\Http\Requests\UpdateArchiveLocationRequest;
use App\Modules\Archive\Http\Resources\ArchiveCategoryResource;
use App\Modules\Archive\Http\Resources\ArchiveLocationResource;
use App\Modules\Archive\Models\ArchiveLocation;
use App\Modules\Archive\Services\ArchiveService;
use App\Modules\Documents\Http\Resources\DocumentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function __construct(
        private readonly ArchiveService $service
    ) {}

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ArchiveLocation::class);
        $organizationId = $request->validate(['organization_id' => ['required', 'uuid']])['organization_id'];

        return ApiResponse::success($this->service->stats($organizationId));
    }

    public function locationTree(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ArchiveLocation::class);
        $organizationId = $request->validate(['organization_id' => ['required', 'uuid']])['organization_id'];

        return ApiResponse::success(
            ArchiveLocationResource::collection($this->service->locationTree($organizationId))
        );
    }

    public function showLocation(string $location): JsonResponse
    {
        $model = $this->service->showLocation($location);
        $this->authorize('view', $model);

        return ApiResponse::success(new ArchiveLocationResource($model));
    }

    public function storeLocation(StoreArchiveLocationRequest $request): JsonResponse
    {
        $this->authorize('create', ArchiveLocation::class);
        $location = $this->service->createLocation($request->validated(), $request->user());

        return ApiResponse::success(new ArchiveLocationResource($location), 'Location created', 201);
    }

    public function updateLocation(UpdateArchiveLocationRequest $request, string $location): JsonResponse
    {
        $model = $this->service->showLocation($location);
        $this->authorize('update', $model);
        $updated = $this->service->updateLocation($location, $request->validated(), $request->user());

        return ApiResponse::success(new ArchiveLocationResource($updated), 'Location updated');
    }

    public function destroyLocation(Request $request, string $location): JsonResponse
    {
        $model = $this->service->showLocation($location);
        $this->authorize('delete', $model);
        $this->service->deleteLocation($location, $request->user());

        return ApiResponse::success(null, 'Location deleted');
    }

    public function categoryTree(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ArchiveLocation::class);
        $organizationId = $request->validate(['organization_id' => ['required', 'uuid']])['organization_id'];

        return ApiResponse::success(
            ArchiveCategoryResource::collection($this->service->categoryTree($organizationId))
        );
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $this->authorize('create', ArchiveLocation::class);
        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'parent_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = $this->service->createCategory($data, $request->user());

        return ApiResponse::success(new ArchiveCategoryResource($category->load('children')), 'Classification created', 201);
    }

    public function updateCategory(Request $request, string $category): JsonResponse
    {
        $this->authorize('create', ArchiveLocation::class);
        $data = $request->validate([
            'parent_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'code' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updated = $this->service->updateCategory($category, $data, $request->user());

        return ApiResponse::success(new ArchiveCategoryResource($updated), 'Classification updated');
    }

    public function destroyCategory(string $category): JsonResponse
    {
        $this->authorize('create', ArchiveLocation::class);
        $this->service->deleteCategory($category);

        return ApiResponse::success(null, 'Classification deleted');
    }

    public function digital(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ArchiveLocation::class);
        $filters = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'search' => ['nullable', 'string', 'max:255'],
            'media_type' => ['nullable', 'in:digital,physical,hybrid'],
            'status' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->service->paginateArchive(
            array_merge($filters, [
                'status' => $filters['status'] ?? 'archived',
                'media_type' => $filters['media_type'] ?? null,
            ]),
            (int) ($filters['per_page'] ?? 15)
        );

        return ApiResponse::success(
            DocumentResource::collection($paginator)->response()->getData(true)
        );
    }

    public function physical(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ArchiveLocation::class);
        $filters = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->service->paginatePhysical($filters, (int) ($filters['per_page'] ?? 15));

        return ApiResponse::success(
            DocumentResource::collection($paginator)->response()->getData(true)
        );
    }

    public function hybrid(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ArchiveLocation::class);
        $filters = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->service->paginateHybrid($filters, (int) ($filters['per_page'] ?? 15));

        return ApiResponse::success(
            DocumentResource::collection($paginator)->response()->getData(true)
        );
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ArchiveLocation::class);
        $data = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'query' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->service->lookup($data['organization_id'], $data['query']);

        if (($result['type'] ?? null) === 'location') {
            return ApiResponse::success([
                'type' => 'location',
                'path' => $result['path'] ?? [],
                'location' => new ArchiveLocationResource($result['location']),
            ]);
        }

        return ApiResponse::success([
            'type' => 'document',
            'location_path' => $result['location_path'] ?? [],
            'document' => new DocumentResource($result['document']),
        ]);
    }

    public function assignLocation(Request $request, string $document): JsonResponse
    {
        $this->authorize('create', ArchiveLocation::class);
        $data = $request->validate([
            'location_id' => ['nullable', 'uuid', 'exists:archive_locations,id'],
            'media_type' => ['nullable', 'in:digital,physical,hybrid'],
        ]);

        $updated = $this->service->assignDocumentLocation(
            $document,
            $data['location_id'] ?? null,
            $data['media_type'] ?? null,
            $request->user()
        );

        return ApiResponse::success(new DocumentResource($updated), 'Archive location assigned');
    }

    public function archiveDocument(Request $request, string $document): JsonResponse
    {
        $this->authorize('create', ArchiveLocation::class);
        $data = $request->validate([
            'location_id' => ['nullable', 'uuid', 'exists:archive_locations,id'],
        ]);

        $archived = $this->service->archiveDocument(
            $document,
            $request->user(),
            $data['location_id'] ?? null
        );

        return ApiResponse::success(new DocumentResource($archived), 'Document archived');
    }
}
