<?php

namespace App\Modules\Authentication\Services;

use App\Models\User;
use App\Modules\Authentication\DTOs\LoginDTO;
use App\Modules\Authentication\Enums\LoginStatus;
use App\Modules\Authentication\Events\LoginFailed;
use App\Modules\Authentication\Events\UserLoggedIn;
use App\Modules\Authentication\Events\UserLoggedOut;
use App\Modules\Authentication\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly DeviceDetectorService $deviceDetector,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function login(LoginDTO $dto, Request $request): array
    {
        $user = $this->authRepository->findByEmail($dto->email);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            if ($user) {
                $user->registerFailedLogin();
            }

            event(new LoginFailed(
                email: $dto->email,
                ipAddress: $dto->ipAddress ?? $request->ip() ?? '0.0.0.0',
                reason: 'Invalid credentials',
                userAgent: $dto->userAgent ?? $request->userAgent(),
            ));

            $this->authRepository->createLoginActivity([
                'user_id' => $user?->id,
                'email' => $dto->email,
                'status' => LoginStatus::Failed->value,
                'ip_address' => $dto->ipAddress ?? $request->ip(),
                'user_agent' => $dto->userAgent ?? $request->userAgent(),
                'failure_reason' => 'Invalid credentials',
                ...$this->deviceDetector->detect($request),
            ]);

            throw ValidationException::withMessages([
                'email' => [__('These credentials do not match our records.')],
            ]);
        }

        if (! $user->is_active) {
            $this->recordFailedAttempt($user, $request, 'Account inactive');

            throw ValidationException::withMessages([
                'email' => [__('Your account is inactive. Please contact the administrator.')],
            ]);
        }

        if ($user->isLocked()) {
            $this->recordFailedAttempt($user, $request, 'Account locked', LoginStatus::Locked);

            throw ValidationException::withMessages([
                'email' => [__('Your account is temporarily locked. Try again later.')],
            ]);
        }

        Auth::login($user, $dto->remember);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $ip = $dto->ipAddress ?? $request->ip() ?? '0.0.0.0';
        $user->markLoginSuccess($ip);

        $deviceMeta = $this->deviceDetector->detect($request);
        $fingerprint = $this->deviceDetector->fingerprint($request);

        $this->authRepository->upsertDevice($user, [
            'device_fingerprint' => $fingerprint,
            'ip_address' => $ip,
            ...$deviceMeta,
        ]);

        $this->authRepository->createLoginActivity([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => LoginStatus::Success->value,
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
            'logged_in_at' => now(),
            ...$deviceMeta,
        ]);

        $token = $user->createToken(
            name: 'spa-session',
            abilities: ['*'],
            expiresAt: $dto->remember ? now()->addDays(30) : now()->addDay(),
        )->plainTextToken;

        event(new UserLoggedIn($user, $ip, $request->userAgent()));

        return [
            'user' => $user->fresh(),
            'token' => $token,
        ];
    }

    public function logout(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $currentToken = $user->currentAccessToken();
            if ($currentToken instanceof PersonalAccessToken) {
                $currentToken->delete();
            }

            $this->authRepository->createLoginActivity([
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => LoginStatus::Logout->value,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'logged_out_at' => now(),
                ...$this->deviceDetector->detect($request),
            ]);

            event(new UserLoggedOut($user, $request->ip() ?? '0.0.0.0'));
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    public function logoutOtherDevices(Request $request, string $password): void
    {
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('The provided password is incorrect.')],
            ]);
        }

        Auth::logoutOtherDevices($password);

        $user->tokens()
            ->where('id', '!=', optional($user->currentAccessToken())->id)
            ->delete();

        $this->authRepository->revokeOtherDevices(
            $user,
            $this->deviceDetector->fingerprint($request)
        );
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => strtolower($email)]);
    }

    public function resetPassword(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'password_changed_at' => now(),
                    'force_password_change' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );
    }

    public function sendEmailVerification(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }

    public function verifyEmail(User $user, Request $request): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return true;
    }

    public function me(User $user): User
    {
        return $user->loadMissing(['roles', 'permissions']);
    }

    public function loginActivities(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->authRepository->paginateLoginActivities($user, $perPage);
    }

    public function devices(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->authRepository->paginateDevices($user, $perPage);
    }

    public function revokeDevice(User $user, string $deviceId): void
    {
        $device = $this->authRepository->findDeviceForUser($user, $deviceId);

        if (! $device) {
            abort(404, 'Device not found.');
        }

        $this->authRepository->revokeDevice($device);
    }

    public function sessions(Request $request): LengthAwarePaginator
    {
        /** @var User $user */
        $user = $request->user();

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->paginate(15);
    }

    public function revokeSession(User $user, string $sessionId): void
    {
        $deleted = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        if (! $deleted) {
            abort(404, 'Session not found.');
        }
    }

    private function recordFailedAttempt(
        User $user,
        Request $request,
        string $reason,
        LoginStatus $status = LoginStatus::Failed
    ): void {
        event(new LoginFailed(
            email: $user->email,
            ipAddress: $request->ip() ?? '0.0.0.0',
            reason: $reason,
            userAgent: $request->userAgent(),
        ));

        $this->authRepository->createLoginActivity([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => $status->value,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'failure_reason' => $reason,
            ...$this->deviceDetector->detect($request),
        ]);
    }
}
