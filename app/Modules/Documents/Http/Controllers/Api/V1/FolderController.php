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
            'include_hidden' => ['sometimes', 'boolean'],
        ]);

        $tree = $this->service->folderTree(
            $request->input('organization_id'),
            $request->boolean('include_hidden')
        );

        return ApiResponse::success(FolderResource::collection($tree));
    }

    public function store(FolderRequest $request): JsonResponse
    {
        $this->authorize('create', Folder::class);

        $folder = $this->service->createFolder($request->validated(), $request->user());

        return ApiResponse::success(new FolderResource($folder), 'Folder created', 201);
    }

    public function update(FolderRequest $request, string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('update', $model);

        $data = $request->validated();
        unset($data['organization_id']);

        $updated = $this->service->updateFolder($folder, $data, $request->user());

        return ApiResponse::success(new FolderResource($updated), 'Folder updated');
    }

    public function rename(Request $request, string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('update', $model);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $updated = $this->service->renameFolder($folder, $data['name'], $request->user());

        return ApiResponse::success(new FolderResource($updated), 'Folder renamed');
    }

    public function lock(Request $request, string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('update', $model);

        $updated = $this->service->lockFolder($folder, $request->user());

        return ApiResponse::success(new FolderResource($updated), 'Folder locked');
    }

    public function unlock(Request $request, string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('update', $model);

        $updated = $this->service->unlockFolder($folder, $request->user());

        return ApiResponse::success(new FolderResource($updated), 'Folder unlocked');
    }

    public function hide(Request $request, string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('update', $model);

        $updated = $this->service->hideFolder($folder, $request->user());

        return ApiResponse::success(new FolderResource($updated), 'Folder hidden');
    }

    public function unhide(Request $request, string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('update', $model);

        $updated = $this->service->unhideFolder($folder, $request->user());

        return ApiResponse::success(new FolderResource($updated), 'Folder unhidden');
    }

    public function destroy(string $folder): JsonResponse
    {
        $model = Folder::query()->findOrFail($folder);
        $this->authorize('delete', $model);

        $this->service->deleteFolder($folder);

        return ApiResponse::success(null, 'Folder deleted');
    }
}
