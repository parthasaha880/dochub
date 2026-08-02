<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Organization storage quota
    |--------------------------------------------------------------------------
    |
    | Used by Dashboard "Total Disk Space Usage" card. Over-usage is shown
    | when used bytes exceed this quota.
    |
    */
    'storage_quota_gb' => (float) env('EDAMS_STORAGE_QUOTA_GB', 10),

    /*
    |--------------------------------------------------------------------------
    | Workflow email notifications
    |--------------------------------------------------------------------------
    |
    | In-app (database) notifications always fire. Email is off by default so
    | approval actions stay fast on local/dev mail setups.
    |
    */
    'workflow_notify_mail' => (bool) env('EDAMS_WORKFLOW_NOTIFY_MAIL', false),

    /*
    |--------------------------------------------------------------------------
    | Email change OTP
    |--------------------------------------------------------------------------
    */
    'email_change_otp_ttl_minutes' => (int) env('EDAMS_EMAIL_CHANGE_OTP_TTL', 10),
    'email_change_otp_max_attempts' => (int) env('EDAMS_EMAIL_CHANGE_OTP_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Password reset OTP
    |--------------------------------------------------------------------------
    */
    'password_reset_otp_ttl_minutes' => (int) env('EDAMS_PASSWORD_RESET_OTP_TTL', 10),
    'password_reset_otp_max_attempts' => (int) env('EDAMS_PASSWORD_RESET_OTP_ATTEMPTS', 3),
    'password_reset_otp_max_per_30_minutes' => (int) env('EDAMS_PASSWORD_RESET_OTP_MAX_30M', 4),
    'password_reset_otp_max_per_day' => (int) env('EDAMS_PASSWORD_RESET_OTP_MAX_DAY', 7),
    'password_reset_lock_hours' => (int) env('EDAMS_PASSWORD_RESET_LOCK_HOURS', 72),
];
