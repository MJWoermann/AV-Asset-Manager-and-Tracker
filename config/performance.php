<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Soft resource limits (availability / multi-user)
    |--------------------------------------------------------------------------
    |
    | Defaults protect a shared LEMP host from one oversized import, export,
    | or compare monopolising PHP-FPM / RAM. Raise via .env or:
    |   php artisan app:performance-limits set <key> <value>
    | Stored overrides live in the settings table and win over .env until reset.
    |
    */

    'import_max_rows' => (int) env('PERF_IMPORT_MAX_ROWS', 10000),

    'import_commit_chunk' => (int) env('PERF_IMPORT_COMMIT_CHUNK', 250),

    'export_max_rows' => (int) env('PERF_EXPORT_MAX_ROWS', 25000),

    'compare_max_list_items' => (int) env('PERF_COMPARE_MAX_LIST_ITEMS', 15000),

    'backup_default_keep' => (int) env('PERF_BACKUP_DEFAULT_KEEP', 7),

    'backup_force_queue' => filter_var(env('PERF_BACKUP_FORCE_QUEUE', true), FILTER_VALIDATE_BOOL),

    'migrate_force_queue' => filter_var(env('PERF_MIGRATE_FORCE_QUEUE', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Named HTTP rate limits (requests per minute)
    |--------------------------------------------------------------------------
    */

    'throttle_import_per_minute' => (int) env('PERF_THROTTLE_IMPORT', 10),

    'throttle_export_per_minute' => (int) env('PERF_THROTTLE_EXPORT', 10),

    'throttle_compare_per_minute' => (int) env('PERF_THROTTLE_COMPARE', 10),

    'throttle_backup_per_minute' => (int) env('PERF_THROTTLE_BACKUP', 3),

];
