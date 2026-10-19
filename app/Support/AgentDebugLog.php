<?php

namespace App\Support;

/**
 * Temporary debug-session logger (session aa8485). Dual-writes local NDJSON + HTTP ingest
 * so remote app hosts can reach the Cursor collector via DEBUG_LOG_INGEST_URL.
 */
class AgentDebugLog
{
    private const SESSION_ID = 'aa8485';

    private const LOG_FILENAME = 'debug-aa8485.log';

    private const DEFAULT_INGEST = 'http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function write(
        string $hypothesisId,
        string $location,
        string $message,
        array $data = [],
        string $runId = 'pre-fix',
    ): void {
        $payload = [
            'sessionId' => self::SESSION_ID,
            'runId' => $runId,
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => array_merge([
                'appUrl' => (string) config('app.url'),
                'hostname' => gethostname() ?: null,
            ], $data),
            'timestamp' => (int) (microtime(true) * 1000),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return;
        }

        $line = $json."\n";

        // Workspace root (Cursor can read when the host is the workspace / SSH remote).
        @file_put_contents(base_path(self::LOG_FILENAME), $line, FILE_APPEND);

        // Always-writable Laravel path on typical remote deploys.
        @file_put_contents(storage_path('logs/'.self::LOG_FILENAME), $line, FILE_APPEND);

        // HTTP ingest — set DEBUG_LOG_INGEST_URL on remote to a tunnel/LAN URL of the Cursor collector.
        $url = (string) env('DEBUG_LOG_INGEST_URL', self::DEFAULT_INGEST);
        if ($url === '') {
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Debug-Session-Id: ".self::SESSION_ID."\r\n",
                'content' => $json,
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);
        @file_get_contents($url, false, $context);
    }
}
