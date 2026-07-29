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
];
