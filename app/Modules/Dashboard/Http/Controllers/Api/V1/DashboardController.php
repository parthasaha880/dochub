<?php

namespace App\Modules\Dashboard\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service
    ) {}

    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'days' => ['nullable', 'integer', 'min:7', 'max:90'],
        ]);

        return ApiResponse::success(
            $this->service->summary(
                $data['organization_id'],
                $request->user(),
                (int) ($data['days'] ?? 30)
            )
        );
    }
}
