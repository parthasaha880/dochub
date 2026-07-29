<?php

namespace App\Modules\Reports\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function preview(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'type' => ['required', Rule::in(['inventory', 'workflow', 'audit', 'shares'])],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $rows = match ($data['type']) {
            'inventory' => $this->service->inventory($data['organization_id']),
            'workflow' => $this->service->workflowSummary($data['organization_id']),
            'audit' => $this->service->auditTrail($data['organization_id'], (int) ($data['days'] ?? 30)),
            'shares' => $this->service->shares($data['organization_id']),
        };

        return ApiResponse::success([
            'type' => $data['type'],
            'count' => $rows->count(),
            'rows' => $rows->take(100)->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('reports.export'), 403);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'type' => ['required', Rule::in(['inventory', 'workflow', 'audit', 'shares'])],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        return $this->service->csv($data['type'], $data['organization_id'], (int) ($data['days'] ?? 30));
    }
}
