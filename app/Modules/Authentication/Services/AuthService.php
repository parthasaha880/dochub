<?php

namespace App\Modules\Authentication\Services;

use App\Models\User;
use App\Modules\Authentication\DTOs\LoginDTO;
use App\Modules\Authentication\Enums\LoginStatus;
use App\Modules\Authentication\Events\LoginFailed;
use App\Modules\Authentication\Events\UserLoggedIn;
use App\Modules\Authentication\Events\UserLoggedOut;
use App\Modules\Authentication\Models\EmailChangeOtp;
use App\Modules\Authentication\Models\PasswordResetOtp;
use App\Modules\Authentication\Notifications\EmailChangeOtpNotification;
use App\Modules\Authentication\Notifications\PasswordResetOtpNotification;
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

    /**
     * Send password recovery OTP. Always returns success metadata so emails
     * cannot be enumerated by the public forgot-password endpoint.
     *
     * @return array{sent: bool, expires_in_minutes: int}
     */
    public function requestPasswordResetOtp(string $email, Request $request): array
    {
        $email = Str::lower(trim($email));
        $ttl = max(1, (int) config('edams.password_reset_otp_ttl_minutes', 10));
        $user = User::query()->where('email', $email)->where('is_active', true)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            PasswordResetOtp::query()
                ->where('user_id', $user->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            PasswordResetOtp::query()->create([
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
                'expires_at' => now()->addMinutes($ttl),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ]);

            try {
                $user->notify(new PasswordResetOtpNotification($code, $ttl));
            } catch (\Throwable $e) {
                report($e);
                // Still return generic success to avoid email enumeration,
                // but log the failure for ops.
            }
        }

        return [
            'sent' => true,
            'expires_in_minutes' => $ttl,
        ];
    }

    public function resetPasswordWithOtp(array $data): User
    {
        $email = Str::lower(trim((string) $data['email']));
        $code = trim((string) $data['otp']);
        $password = (string) $data['password'];

        $user = User::query()->where('email', $email)->where('is_active', true)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['We could not reset the password for this email.'],
            ]);
        }

        $otp = PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => ['No active recovery code found. Please request a new one.'],
            ]);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['This code has expired. Please request a new one.'],
            ]);
        }

        $maxAttempts = max(1, (int) config('edams.password_reset_otp_max_attempts', 5));
        if ($otp->attempts >= $maxAttempts) {
            $otp->forceFill(['consumed_at' => now()])->save();
            throw ValidationException::withMessages([
                'otp' => ['Too many invalid attempts. Please request a new code.'],
            ]);
        }

        if (! hash_equals($otp->code, $code)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages([
                'otp' => ['Invalid recovery code.'],
            ]);
        }

        return DB::transaction(function () use ($user, $otp, $password) {
            $user->forceFill([
                'password' => $password,
                'password_changed_at' => now(),
                'force_password_change' => false,
                'remember_token' => Str::random(60),
                'updated_by' => $user->id,
            ])->save();

            $otp->forceFill([
                'verified_at' => now(),
                'consumed_at' => now(),
            ])->save();

            $user->tokens()->delete();

            event(new PasswordReset($user));

            return $user->fresh();
        });
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

                $user->tokens()->delete();

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

    public function updateProfile(User $user, array $data): User
    {
        $user->fill(collect($data)->only([
            'name',
            'phone',
            'timezone',
            'locale',
            'theme',
        ])->all());
        $user->updated_by = $user->id;
        $user->save();

        return $user->fresh()->loadMissing(['roles', 'permissions']);
    }

    /**
     * Send OTP to the user's current email before changing to a new address.
     *
     * @return array{otp: EmailChangeOtp, expires_in_minutes: int}
     */
    public function requestEmailChange(User $user, string $newEmail, Request $request): array
    {
        $newEmail = Str::lower(trim($newEmail));

        if ($newEmail === Str::lower((string) $user->email)) {
            throw ValidationException::withMessages([
                'email' => ['New email must be different from your current email.'],
            ]);
        }

        if (User::query()->where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This email is already in use.'],
            ]);
        }

        $ttl = max(1, (int) config('edams.email_change_otp_ttl_minutes', 10));
        $code = (string) random_int(100000, 999999);

        // Invalidate previous unused OTPs for this user
        EmailChangeOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $otp = EmailChangeOtp::query()->create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'new_email' => $newEmail,
            'code' => $code,
            'expires_at' => now()->addMinutes($ttl),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        try {
            $user->notify(new EmailChangeOtpNotification($code, $newEmail, $ttl));
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'email' => ['OTP was generated but email could not be sent. Please check mail settings or try again. ('.$e->getMessage().')'],
            ]);
        }

        return [
            'otp' => $otp,
            'expires_in_minutes' => $ttl,
        ];
    }

    public function confirmEmailChange(User $user, string $code): User
    {
        $otp = EmailChangeOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => ['No active email change request found. Please request a new code.'],
            ]);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['This code has expired. Please request a new one.'],
            ]);
        }

        $maxAttempts = max(1, (int) config('edams.email_change_otp_max_attempts', 5));
        if ($otp->attempts >= $maxAttempts) {
            $otp->forceFill(['consumed_at' => now()])->save();
            throw ValidationException::withMessages([
                'otp' => ['Too many invalid attempts. Please request a new code.'],
            ]);
        }

        if (! hash_equals($otp->code, trim($code))) {
            $otp->increment('attempts');
            throw ValidationException::withMessages([
                'otp' => ['Invalid verification code.'],
            ]);
        }

        if (User::query()->where('email', $otp->new_email)->where('id', '!=', $user->id)->exists()) {
            $otp->forceFill(['consumed_at' => now()])->save();
            throw ValidationException::withMessages([
                'email' => ['This email is already in use.'],
            ]);
        }

        return DB::transaction(function () use ($user, $otp) {
            $user->forceFill([
                'email' => $otp->new_email,
                'email_verified_at' => now(),
                'updated_by' => $user->id,
            ])->save();

            $otp->forceFill([
                'verified_at' => now(),
                'consumed_at' => now(),
            ])->save();

            return $user->fresh()->loadMissing(['roles', 'permissions']);
        });
    }

    public function paginateEmailChangeOtps(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginateOtpBook(array_merge($filters, ['type' => 'email_change']), $perPage);
    }

    /**
     * Unified OTP book for email-change and password-reset codes.
     *
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateOtpBook(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $type = $filters['type'] ?? null;
        $rows = collect();

        if ($type !== 'password_reset') {
            $emailQuery = EmailChangeOtp::query()->with(['user:id,name,email']);
            $this->applyOtpStatusFilter($emailQuery, $filters['status'] ?? null);
            if (! empty($filters['search'])) {
                $search = '%'.$filters['search'].'%';
                $emailQuery->where(function ($q) use ($search) {
                    $q->where('current_email', 'like', $search)
                        ->orWhere('new_email', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $search)->orWhere('email', 'like', $search));
                });
            }
            $rows = $rows->merge($emailQuery->get()->map(fn (EmailChangeOtp $otp) => (object) [
                'id' => $otp->id,
                'type' => 'email_change',
                'user' => $otp->user ? [
                    'id' => $otp->user->id,
                    'name' => $otp->user->name,
                    'email' => $otp->user->email,
                ] : null,
                'email' => $otp->current_email,
                'detail' => $otp->new_email,
                'code' => $otp->code,
                'status' => $otp->statusLabel(),
                'expires_at' => $otp->expires_at?->toIso8601String(),
                'created_at' => $otp->created_at?->toIso8601String(),
                'ip_address' => $otp->ip_address,
                'sort_at' => $otp->created_at,
            ]));
        }

        if ($type !== 'email_change') {
            $passwordQuery = PasswordResetOtp::query()->with(['user:id,name,email']);
            $this->applyOtpStatusFilter($passwordQuery, $filters['status'] ?? null);
            if (! empty($filters['search'])) {
                $search = '%'.$filters['search'].'%';
                $passwordQuery->where(function ($q) use ($search) {
                    $q->where('email', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $search)->orWhere('email', 'like', $search));
                });
            }
            $rows = $rows->merge($passwordQuery->get()->map(fn (PasswordResetOtp $otp) => (object) [
                'id' => $otp->id,
                'type' => 'password_reset',
                'user' => $otp->user ? [
                    'id' => $otp->user->id,
                    'name' => $otp->user->name,
                    'email' => $otp->user->email,
                ] : null,
                'email' => $otp->email,
                'detail' => null,
                'code' => $otp->code,
                'status' => $otp->statusLabel(),
                'expires_at' => $otp->expires_at?->toIso8601String(),
                'created_at' => $otp->created_at?->toIso8601String(),
                'ip_address' => $otp->ip_address,
                'sort_at' => $otp->created_at,
            ]));
        }

        $sorted = $rows->sortByDesc(fn ($row) => $row->sort_at?->timestamp ?? 0)->values();
        $page = max(1, (int) request()->input('page', 1));
        $perPage = max(1, min(100, $perPage));
        $slice = $sorted->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function applyOtpStatusFilter($query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        match ($status) {
            'pending' => $query->whereNull('consumed_at')->where('expires_at', '>', now()),
            'used' => $query->whereNotNull('consumed_at'),
            'expired' => $query->whereNull('consumed_at')->where('expires_at', '<=', now()),
            default => null,
        };
    }

    public function updateAvatar(User $user, \Illuminate\Http\UploadedFile $file): User
    {
        $disk = 'public';
        $directory = 'avatars';

        if ($user->avatar_path) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($user->avatar_path);
        }

        $path = $file->store($directory, $disk);
        $user->forceFill([
            'avatar_path' => $path,
            'updated_by' => $user->id,
        ])->save();

        return $user->fresh()->loadMissing(['roles', 'permissions']);
    }

    public function removeAvatar(User $user): User
    {
        if ($user->avatar_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill([
                'avatar_path' => null,
                'updated_by' => $user->id,
            ])->save();
        }

        return $user->fresh()->loadMissing(['roles', 'permissions']);
    }

    public function avatarResponse(User $user): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        if (! $user->avatar_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path)) {
            abort(404, 'Avatar not found.');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->response($user->avatar_path);
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
