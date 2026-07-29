<?php

namespace App\Modules\Documents\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Documents\Http\Requests\FolderRequest;
use App\Modules\Documents\Http\Resources\FolderResource;
use App\Modules\Documents\Models\Folder;
use App\Modules\Documents\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function __construct(
        private readonly DocumentService $service
    ) {}

    public function tree(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Folder::class);

        $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
        ]);

        $tree = $this->service->folderTree($request->input('organization_id'));

        return ApiResponse::success(FolderResource::collection($tree));
    }

    public function store(FolderRequest $request): JsonResponse
    {
        $this->authorize('create', Folder::class);

        $folder = $this->service->createFolder($request->validated());

        return ApiResponse::success(new FolderResource($folder), 'Folder created', 201);
    }

    public function update(FolderRequest $request, string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('update', $model);

        $data = $request->validated();
        unset($data['organization_id']);

        $updated = $this->service->updateFolder($folder, $data);

        return ApiResponse::success(new FolderResource($updated), 'Folder updated');
    }

    public function destroy(string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('delete', $model);

        $this->service->deleteFolder($folder);

        return ApiResponse::success(null, 'Folder deleted');
    }
}
