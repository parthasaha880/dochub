<?php

namespace App\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Modules\Authentication\Models\LoginActivity;
use App\Modules\Authentication\Models\UserDevice;
use App\Modules\Authentication\Notifications\WelcomeUserNotification;
use App\Modules\Organization\Models\Employee;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    /**
     * Spatie Permission guard (keep roles on web while authenticating via Sanctum).
     */
    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'username',
        'phone',
        'employee_id',
        'avatar_path',
        'is_active',
        'force_password_change',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'email_verified_at',
        'password',
        'timezone',
        'locale',
        'theme',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Prefer an explicit recipient on welcome notifications (new account / new email).
     */
    public function routeNotificationForMail(object $notification): string
    {
        if ($notification instanceof WelcomeUserNotification && filled($notification->recipientEmail)) {
            return (string) $notification->recipientEmail;
        }

        return (string) $this->email;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function markLoginSuccess(string $ipAddress): void
    {
        $this->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ])->save();
    }

    public function registerFailedLogin(int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $attempts = $this->failed_login_attempts + 1;
        $attributes = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $attributes['locked_until'] = now()->addMinutes($lockMinutes);
            $attributes['failed_login_attempts'] = 0;
        }

        $this->forceFill($attributes)->save();
    }
}
