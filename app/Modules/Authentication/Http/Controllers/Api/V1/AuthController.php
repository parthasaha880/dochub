<?php

namespace App\Modules\Authentication\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Authentication\DTOs\LoginDTO;
use App\Modules\Authentication\Http\Requests\ForgotPasswordRequest;
use App\Modules\Authentication\Http\Requests\LoginRequest;
use App\Modules\Authentication\Http\Requests\LogoutOtherDevicesRequest;
use App\Modules\Authentication\Http\Requests\ResetPasswordRequest;
use App\Modules\Authentication\Http\Resources\LoginActivityResource;
use App\Modules\Authentication\Http\Resources\UserDeviceResource;
use App\Modules\Authentication\Http\Resources\UserResource;
use App\Modules\Authentication\Services\AuthService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            LoginDTO::fromArray([
                ...$request->validated(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]),
            $request
        );

        return ApiResponse::success([
            'user' => new UserResource($result['user']->load(['roles', 'permissions'])),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ], 'Logged in successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return ApiResponse::success(new UserResource($user));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendPasswordResetLink($request->validated('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return ApiResponse::error(__($status), 422);
        }

        return ApiResponse::success(null, __($status));
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(__($status), 422);
        }

        return ApiResponse::success(null, __($status));
    }

    public function sendVerificationEmail(Request $request): JsonResponse
    {
        $this->authService->sendEmailVerification($request->user());

        return ApiResponse::success(null, 'Verification link sent.');
    }

    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        $this->authService->verifyEmail($request->user(), $request);

        return ApiResponse::success(null, 'Email verified successfully.');
    }

    public function loginActivities(Request $request): JsonResponse
    {
        $activities = $this->authService->loginActivities(
            $request->user(),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            LoginActivityResource::collection($activities)->response()->getData(true)
        );
    }

    public function devices(Request $request): JsonResponse
    {
        $devices = $this->authService->devices(
            $request->user(),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            UserDeviceResource::collection($devices)->response()->getData(true)
        );
    }

    public function revokeDevice(Request $request, string $device): JsonResponse
    {
        $deviceModel = $request->user()->devices()->whereKey($device)->firstOrFail();
        $this->authorize('delete', $deviceModel);
        $this->authService->revokeDevice($request->user(), $device);

        return ApiResponse::success(null, 'Device revoked successfully.');
    }

    public function logoutOtherDevices(LogoutOtherDevicesRequest $request): JsonResponse
    {
        $this->authService->logoutOtherDevices($request, $request->validated('password'));

        return ApiResponse::success(null, 'Other devices logged out successfully.');
    }

    public function sessions(Request $request): JsonResponse
    {
        $sessions = $this->authService->sessions($request);

        return ApiResponse::success($sessions);
    }

    public function revokeSession(Request $request, string $session): JsonResponse
    {
        $this->authService->revokeSession($request->user(), $session);

        return ApiResponse::success(null, 'Session revoked successfully.');
    }
}
