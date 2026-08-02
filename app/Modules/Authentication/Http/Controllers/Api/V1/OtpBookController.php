<?php

namespace App\Modules\Authentication\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Authentication\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpBookController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('otp.view'), 403, 'You do not have permission to view the OTP book.');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,used,expired'],
            'type' => ['nullable', 'in:email_change,password_reset'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->authService->paginateOtpBook(
            $filters,
            (int) ($filters['per_page'] ?? 20)
        );

        return ApiResponse::success([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
