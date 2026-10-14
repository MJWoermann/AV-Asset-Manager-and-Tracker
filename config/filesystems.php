<?php

$qnapThrow = (bool) env('QNAP_THROW', true);
$sharepointThrow = (bool) env('SHAREPOINT_THROW', true);

$qnapDisk = match (env('QNAP_DRIVER', 'mount')) {
    'sftp' => [
        'driver' => 'sftp',
        'host' => env('QNAP_SFTP_HOST'),
        'username' => env('QNAP_SFTP_USERNAME'),
        'password' => env('QNAP_SFTP_PASSWORD'),
        'privateKey' => env('QNAP_SFTP_PRIVATE_KEY'),
        'passphrase' => env('QNAP_SFTP_PASSPHRASE'),
        'port' => (int) env('QNAP_SFTP_PORT', 22),
        'root' => env('QNAP_SFTP_ROOT', '/'),
        'timeout' => (int) env('QNAP_SFTP_TIMEOUT', 30),
        'throw' => $qnapThrow,
        'report' => false,
    ],
    's3' => [
        'driver' => 's3',
        'key' => env('QNAP_S3_ACCESS_KEY_ID'),
        'secret' => env('QNAP_S3_SECRET_ACCESS_KEY'),
        'region' => env('QNAP_S3_REGION', 'us-east-1'),
        'bucket' => env('QNAP_S3_BUCKET'),
        'url' => env('QNAP_S3_URL'),
        'endpoint' => env('QNAP_S3_ENDPOINT'),
        'use_path_style_endpoint' => env('QNAP_S3_USE_PATH_STYLE_ENDPOINT', true),
        'root' => env('QNAP_S3_ROOT', ''),
        'throw' => $qnapThrow,
        'report' => false,
    ],
    default => [
        'driver' => 'local',
        'root' => env('QNAP_MOUNT_PATH', storage_path('app/qnap')),
        'throw' => $qnapThrow,
        'report' => false,
    ],
};

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3", "sharepoint"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'qnap' => $qnapDisk,

        'sharepoint' => [
            'driver' => 'sharepoint',
            'auth_mode' => 'client_credentials',
            'client_id' => env('GRAPH_CLIENT_ID'),
            'client_secret' => env('GRAPH_CLIENT_SECRET'),
            'tenant_id' => env('GRAPH_TENANT_ID', 'common'),
            'drive_id' => env('SHAREPOINT_DRIVE_ID'),
            'root_item_id' => env('SHAREPOINT_ROOT_ITEM_ID'),
            'prefix' => env('SHAREPOINT_PREFIX', ''),
            'copy_monitor_timeout' => (int) env('SHAREPOINT_COPY_MONITOR_TIMEOUT', 300),
            'copy_monitor_interval_ms' => (int) env('SHAREPOINT_COPY_MONITOR_INTERVAL_MS', 1000),
            'throw' => $sharepointThrow,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
