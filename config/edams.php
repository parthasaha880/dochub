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
];
