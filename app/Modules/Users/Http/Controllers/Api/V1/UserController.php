<?php

namespace App\Modules\Users\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Http\Requests\StoreUserRequest;
use App\Modules\Users\Http\Requests\UpdateUserRequest;
use App\Modules\Users\Http\Resources\AdminUserResource;
use App\Modules\Users\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $paginator = $this->service->paginateUsers(
            $request->only(['search', 'is_active', 'role']),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            AdminUserResource::collection($paginator)->response()->getData(true)
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->service->createUser($request->validated());

        return ApiResponse::success(new AdminUserResource($user), 'User created successfully', 201);
    }

    public function show(string $user): JsonResponse
    {
        $model = $this->service->showUser($user);
        $this->authorize('view', $model);

        return ApiResponse::success(new AdminUserResource($model));
    }

    public function update(UpdateUserRequest $request, string $user): JsonResponse
    {
        $model = $this->service->showUser($user);
        $this->authorize('update', $model);

        $updated = $this->service->updateUser($user, $request->validated(), $request->user());

        return ApiResponse::success(new AdminUserResource($updated), 'User updated successfully');
    }

    public function destroy(Request $request, string $user): JsonResponse
    {
        $model = $this->service->showUser($user);
        $this->authorize('delete', $model);

        $this->service->deleteUser($user, $request->user());

        return ApiResponse::success(null, 'User deleted successfully');
    }
}
