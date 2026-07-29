<?php

namespace App\Modules\Users\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Modules\Users\Http\Requests\PermissionRequest;
use App\Modules\Users\Http\Resources\PermissionResource;
use App\Modules\Users\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(
        private readonly UserManagementService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $paginator = $this->service->paginatePermissions(
            $request->only(['search', 'group']),
            (int) $request->integer('per_page', 50)
        );

        return ApiResponse::success(
            PermissionResource::collection($paginator)->response()->getData(true)
        );
    }

    public function store(PermissionRequest $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $permission = $this->service->createPermission($request->validated());

        return ApiResponse::success(new PermissionResource($permission), 'Permission created successfully', 201);
    }

    public function show(string $permission): JsonResponse
    {
        $model = $this->service->showPermission($permission);
        $this->authorize('view', $model);

        return ApiResponse::success(new PermissionResource($model));
    }

    public function update(PermissionRequest $request, string $permission): JsonResponse
    {
        $model = $this->service->showPermission($permission);
        $this->authorize('update', $model);

        $updated = $this->service->updatePermission($permission, $request->validated());

        return ApiResponse::success(new PermissionResource($updated), 'Permission updated successfully');
    }

    public function destroy(string $permission): JsonResponse
    {
        $model = $this->service->showPermission($permission);
        $this->authorize('delete', $model);

        $this->service->deletePermission($permission);

        return ApiResponse::success(null, 'Permission deleted successfully');
    }

    public function groups(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        return ApiResponse::success($this->service->permissionGroups());
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        return ApiResponse::success($this->service->permissionOptions());
    }

    public function grouped(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        return ApiResponse::success($this->service->permissionsByGroup());
    }
}
