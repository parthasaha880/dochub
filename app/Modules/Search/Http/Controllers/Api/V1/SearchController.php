<?php

namespace App\Modules\Search\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Search\Http\Resources\SavedSearchResource;
use App\Modules\Search\Models\SavedSearch;
use App\Modules\Search\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $service
    ) {}

    public function documents(Request $request): JsonResponse
    {
        $this->authorize('search', SavedSearch::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'folder_id' => ['nullable'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'category_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'status' => ['nullable', 'string', 'max:30'],
            'approval_status' => ['nullable', 'string', 'max:30'],
            'confidentiality_level' => ['nullable', 'string', 'max:30'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'extension' => ['nullable', 'string', 'max:20'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'uploader_id' => ['nullable', 'uuid', 'exists:users,id'],
            'owner_id' => ['nullable', 'uuid', 'exists:users,id'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->service->search(
            $this->service->normalizeCriteria($filters),
            (int) ($filters['per_page'] ?? 15)
        );

        return ApiResponse::success(
            DocumentResource::collection($paginator)->response()->getData(true)
        );
    }

    public function facets(Request $request): JsonResponse
    {
        $this->authorize('search', SavedSearch::class);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
        ]);

        return ApiResponse::success($this->service->facets($data['organization_id']));
    }

    public function indexSaved(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SavedSearch::class);

        $organizationId = $request->validate([
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
        ])['organization_id'] ?? null;

        $items = $this->service->listSaved($request->user(), $organizationId);

        return ApiResponse::success(SavedSearchResource::collection($items));
    }

    public function storeSaved(Request $request): JsonResponse
    {
        $this->authorize('create', SavedSearch::class);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'criteria' => ['required', 'array'],
            'is_shared' => ['sometimes', 'boolean'],
        ]);

        $saved = $this->service->createSaved($data, $request->user());

        return ApiResponse::success(new SavedSearchResource($saved), 'Saved search created', 201);
    }

    public function showSaved(Request $request, string $saved): JsonResponse
    {
        $model = $this->service->showSaved($saved, $request->user());
        $this->authorize('view', $model);

        return ApiResponse::success(new SavedSearchResource($model));
    }

    public function updateSaved(Request $request, string $saved): JsonResponse
    {
        $model = $this->service->showSaved($saved, $request->user());
        $this->authorize('update', $model);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'criteria' => ['sometimes', 'required', 'array'],
            'is_shared' => ['sometimes', 'boolean'],
        ]);

        $updated = $this->service->updateSaved($saved, $data, $request->user());

        return ApiResponse::success(new SavedSearchResource($updated), 'Saved search updated');
    }

    public function destroySaved(Request $request, string $saved): JsonResponse
    {
        $model = $this->service->showSaved($saved, $request->user());
        $this->authorize('delete', $model);

        $this->service->deleteSaved($saved, $request->user());

        return ApiResponse::success(null, 'Saved search deleted');
    }
}
