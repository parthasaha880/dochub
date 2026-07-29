<?php

namespace App\Modules\Retention\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Retention\Services\RetentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RetentionController extends Controller
{
    public function __construct(private readonly RetentionService $service) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('retention.view'), 403);

        $filters = $request->validate([
            'organization_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->service->paginate($filters, (int) ($filters['per_page'] ?? 15));

        return ApiResponse::success([
            'data' => collect($paginator->items())->map(fn ($p) => $this->map($p)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('retention.manage'), 403);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:36500'],
            'action_on_expiry' => ['required', Rule::in(['archive', 'soft_delete', 'flag'])],
            'category_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $policy = $this->service->create($data, $request->user());

        return ApiResponse::success($this->map($policy), 'Policy created', 201);
    }

    public function update(Request $request, string $policy): JsonResponse
    {
        abort_unless($request->user()?->can('retention.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'retention_days' => ['sometimes', 'integer', 'min:1', 'max:36500'],
            'action_on_expiry' => ['sometimes', Rule::in(['archive', 'soft_delete', 'flag'])],
            'category_id' => ['nullable', 'uuid', 'exists:document_categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $updated = $this->service->update($policy, $data, $request->user());

        return ApiResponse::success($this->map($updated), 'Policy updated');
    }

    public function destroy(Request $request, string $policy): JsonResponse
    {
        abort_unless($request->user()?->can('retention.manage'), 403);
        $this->service->delete($policy, $request->user());

        return ApiResponse::success(null, 'Policy deleted');
    }

    public function run(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('retention.manage'), 403);

        $data = $request->validate([
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
        ]);

        $run = $this->service->process($data['organization_id'] ?? null);

        return ApiResponse::success($run, 'Retention job completed');
    }

    public function runs(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('retention.view'), 403);

        $data = $request->validate([
            'organization_id' => ['nullable', 'uuid'],
        ]);

        return ApiResponse::success($this->service->recentRuns($data['organization_id'] ?? null));
    }

    private function map($policy): array
    {
        return [
            'id' => $policy->id,
            'organization_id' => $policy->organization_id,
            'name' => $policy->name,
            'code' => $policy->code,
            'description' => $policy->description,
            'retention_days' => $policy->retention_days,
            'action_on_expiry' => $policy->action_on_expiry?->value ?? $policy->action_on_expiry,
            'category_id' => $policy->category_id,
            'is_active' => $policy->is_active,
            'is_default' => $policy->is_default,
        ];
    }
}
