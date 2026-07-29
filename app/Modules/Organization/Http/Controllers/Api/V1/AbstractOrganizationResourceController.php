<?php

namespace App\Modules\Organization\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Organization\Services\OrganizationStructureService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class AbstractOrganizationResourceController extends Controller
{
    public function __construct(
        protected readonly OrganizationStructureService $service
    ) {}

    abstract protected function modelClass(): string;

    abstract protected function resourceClass(): string;

    /**
     * @return array<int, string>
     */
    protected function defaultWith(): array
    {
        return [];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', $this->modelClass());

        $filters = $request->only([
            'organization_id',
            'department_id',
            'section_id',
            'branch_id',
            'unit_id',
            'office_id',
            'designation_id',
            'is_active',
            'employment_status',
            'search',
        ]);
        $filters['with'] = $this->defaultWith();

        $paginator = $this->service->paginate(
            $this->modelClass(),
            $filters,
            (int) $request->integer('per_page', 15)
        );

        /** @var class-string<JsonResource> $resource */
        $resource = $this->resourceClass();

        return ApiResponse::success(
            $resource::collection($paginator)->response()->getData(true)
        );
    }

    public function show(string $id): JsonResponse
    {
        $model = $this->service->show($this->modelClass(), $id, $this->defaultWith());
        $this->authorize('view', $model);

        return ApiResponse::success($this->toResource($model));
    }

    public function destroy(string $id): JsonResponse
    {
        $existing = $this->service->show($this->modelClass(), $id);
        $this->authorize('delete', $existing);

        $this->service->delete($this->modelClass(), $id);

        return ApiResponse::success(null, 'Deleted successfully');
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', $this->modelClass());

        $items = $this->service->options(
            $this->modelClass(),
            $request->string('organization_id')->toString() ?: null
        );

        return ApiResponse::success($items);
    }

    protected function storeValidated(array $payload): JsonResponse
    {
        $this->authorize('create', $this->modelClass());

        $model = $this->service->create($this->modelClass(), $payload);

        return ApiResponse::success(
            $this->toResource($model->load($this->defaultWith())),
            'Created successfully',
            201
        );
    }

    protected function updateValidated(string $id, array $payload): JsonResponse
    {
        $existing = $this->service->show($this->modelClass(), $id);
        $this->authorize('update', $existing);

        $model = $this->service->update($this->modelClass(), $id, $payload);

        return ApiResponse::success(
            $this->toResource($model->load($this->defaultWith())),
            'Updated successfully'
        );
    }

    protected function toResource(Model $model): JsonResource
    {
        $resource = $this->resourceClass();

        return new $resource($model);
    }
}
