<?php

namespace App\Modules\Workflow\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Workflow\Http\Requests\StoreWorkflowRequest;
use App\Modules\Workflow\Http\Requests\UpdateWorkflowRequest;
use App\Modules\Workflow\Http\Resources\WorkflowActionResource;
use App\Modules\Workflow\Http\Resources\WorkflowInstanceResource;
use App\Modules\Workflow\Http\Resources\WorkflowResource;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $paginator = $this->service->paginateWorkflows(
            $request->only(['organization_id', 'is_active', 'search']),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            WorkflowResource::collection($paginator)->response()->getData(true)
        );
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $workflow = $this->service->createWorkflow($request->validated(), $request->user());

        return ApiResponse::success(new WorkflowResource($workflow), 'Workflow created', 201);
    }

    public function show(string $workflow): JsonResponse
    {
        $model = $this->service->showWorkflow($workflow);
        $this->authorize('view', $model);

        return ApiResponse::success(new WorkflowResource($model));
    }

    public function update(UpdateWorkflowRequest $request, string $workflow): JsonResponse
    {
        $model = $this->service->showWorkflow($workflow);
        $this->authorize('update', $model);

        $updated = $this->service->updateWorkflow($workflow, $request->validated(), $request->user());

        return ApiResponse::success(new WorkflowResource($updated), 'Workflow updated');
    }

    public function destroy(string $workflow): JsonResponse
    {
        $model = $this->service->showWorkflow($workflow);
        $this->authorize('delete', $model);

        $this->service->deleteWorkflow($workflow);

        return ApiResponse::success(null, 'Workflow deleted');
    }

    public function inbox(Request $request): JsonResponse
    {
        $this->authorize('viewInbox', Workflow::class);

        $paginator = $this->service->inbox(
            $request->user(),
            $request->only(['organization_id', 'search']),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            WorkflowInstanceResource::collection($paginator)->response()->getData(true)
        );
    }

    public function instances(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $paginator = $this->service->instances(
            $request->only(['organization_id', 'document_id', 'status', 'workflow_id']),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            WorkflowInstanceResource::collection($paginator)->response()->getData(true)
        );
    }

    public function showInstance(string $instance): JsonResponse
    {
        $model = $this->service->showInstance($instance);
        $this->authorize('viewAny', Workflow::class);

        return ApiResponse::success(new WorkflowInstanceResource($model));
    }

    public function submit(Request $request): JsonResponse
    {
        $this->authorize('submit', Workflow::class);

        $data = $request->validate([
            'document_id' => ['required', 'uuid', 'exists:documents,id'],
            'workflow_id' => ['nullable', 'uuid', 'exists:workflows,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $instance = $this->service->submitDocument(
            $data['document_id'],
            $request->user(),
            $data['workflow_id'] ?? null,
            $data['note'] ?? null
        );

        return ApiResponse::success(new WorkflowInstanceResource($instance), 'Document submitted for approval', 201);
    }

    public function approve(Request $request, string $instance): JsonResponse
    {
        $model = $this->service->showInstance($instance);
        $this->authorize('approve', $model);

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->service->approve($instance, $request->user(), $data['comments'] ?? null);

        return ApiResponse::success(new WorkflowInstanceResource($updated), 'Step approved');
    }

    public function reject(Request $request, string $instance): JsonResponse
    {
        $model = $this->service->showInstance($instance);
        $this->authorize('approve', $model);

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->service->reject($instance, $request->user(), $data['comments'] ?? null);

        return ApiResponse::success(new WorkflowInstanceResource($updated), 'Document rejected');
    }

    public function returnInstance(Request $request, string $instance): JsonResponse
    {
        $model = $this->service->showInstance($instance);
        $this->authorize('approve', $model);

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->service->returnToSubmitter($instance, $request->user(), $data['comments'] ?? null);

        return ApiResponse::success(new WorkflowInstanceResource($updated), 'Document returned to submitter');
    }

    public function cancel(Request $request, string $instance): JsonResponse
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->service->cancel($instance, $request->user(), $data['comments'] ?? null);

        return ApiResponse::success(new WorkflowInstanceResource($updated), 'Workflow cancelled');
    }

    public function documentStatus(string $document): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $status = $this->service->documentStatus($document);

        return ApiResponse::success([
            'active_instance' => $status['active_instance']
                ? new WorkflowInstanceResource($status['active_instance'])
                : null,
            'history' => WorkflowInstanceResource::collection($status['history']),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
        ]);

        return ApiResponse::success($this->service->stats($data['organization_id'], $request->user()));
    }

    public function recent(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $actions = $this->service->recentActions($data['organization_id'], $data['limit'] ?? 10);

        return ApiResponse::success(WorkflowActionResource::collection($actions));
    }
}
