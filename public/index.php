<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// #region agent log
$__agentDebugLog = static function (string $hypothesisId, string $message, array $data = []): void {
    $payload = [
        'sessionId' => '00a5d6',
        'runId' => 'pre-fix',
        'hypothesisId' => $hypothesisId,
        'location' => 'public/index.php',
        'message' => $message,
        'data' => $data,
        'timestamp' => (int) round(microtime(true) * 1000),
    ];
    @file_put_contents(
        __DIR__.'/../debug-00a5d6.log',
        json_encode($payload, JSON_UNESCAPED_SLASHES)."\n",
        FILE_APPEND | LOCK_EX
    );
};
$__agentDebugLog('H1', 'index.php entered', [
    'maintenance_php_exists' => file_exists(__DIR__.'/../storage/framework/maintenance.php'),
    'down_file_exists' => file_exists(__DIR__.'/../storage/framework/down'),
    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
]);
// #endregion

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    // #region agent log
    $__agentDebugLog('H1', 'serving maintenance mode 503', [
        'maintenance_path' => $maintenance,
    ]);
    // #endregion
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// #region agent log
$__agentDebugLog('H2', 'laravel bootstrapped; handling request', [
    'app_env' => $app->environment(),
]);
// #endregion

$app->handleRequest(Request::capture());
