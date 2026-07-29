<?php

namespace App\Modules\Users\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Modules\Users\Http\Requests\RoleRequest;
use App\Modules\Users\Http\Resources\RoleResource;
use App\Modules\Users\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private readonly UserManagementService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $paginator = $this->service->paginateRoles(
            $request->only(['search']),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            RoleResource::collection($paginator)->response()->getData(true)
        );
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->service->createRole($request->validated());

        return ApiResponse::success(new RoleResource($role), 'Role created successfully', 201);
    }

    public function show(string $role): JsonResponse
    {
        $model = $this->service->showRole($role);
        $this->authorize('view', $model);

        return ApiResponse::success(new RoleResource($model));
    }

    public function update(RoleRequest $request, string $role): JsonResponse
    {
        $model = $this->service->showRole($role);
        $this->authorize('update', $model);

        $updated = $this->service->updateRole($role, $request->validated());

        return ApiResponse::success(new RoleResource($updated), 'Role updated successfully');
    }

    public function destroy(string $role): JsonResponse
    {
        $model = $this->service->showRole($role);
        $this->authorize('delete', $model);

        $this->service->deleteRole($role);

        return ApiResponse::success(null, 'Role deleted successfully');
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return ApiResponse::success($this->service->roleOptions());
    }
}
