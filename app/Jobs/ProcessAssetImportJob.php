<?php

namespace App\Jobs;

use App\Enums\FileCategory;
use App\Services\FileAttachmentService;
use App\Services\ImportService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Finishes a large asset import after the HTTP response is sent, so nginx/proxies
 * do not 504 while thousands of rows are created/updated.
 *
 * Runs in the same PHP-FPM request via dispatchAfterResponse() (no queue worker required).
 */
class ProcessAssetImportJob
{
    use Dispatchable;

    /**
     * @param  array<int|string, string>  $actions
     * @param  array{
     *     destination?: 'none'|'existing'|'new',
     *     asset_list_id?: int|null,
     *     new_list_name?: string|null,
     *     new_list_type?: 'inventory'|'event'|null,
     *     include_updated?: bool,
     * }  $listOptions
     */
    public function __construct(
        public string $uploadPath,
        public string $preparedPath,
        public string $originalFilename,
        public array $actions,
        public string $defaultDuplicateAction,
        public array $listOptions,
        public ?int $userId,
    ) {}

    public function handle(ImportService $imports, FileAttachmentService $attachments): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        ignore_user_abort(true);

        // #region agent log
        $t0 = microtime(true);
        $log = function (string $message, array $data = []) use ($t0): void {
            @file_put_contents(base_path('debug-d766ca.log'), json_encode([
                'sessionId' => 'd766ca',
                'runId' => 'commit-bg',
                'hypothesisId' => 'COMMIT',
                'location' => 'ProcessAssetImportJob.php:handle',
                'message' => $message,
                'data' => array_merge([
                    'elapsed_ms' => (int) round((microtime(true) - $t0) * 1000),
                ], $data),
                'timestamp' => (int) round(microtime(true) * 1000),
            ])."\n", FILE_APPEND);
        };
        $log('commit_bg_start', ['prepared_path' => $this->preparedPath]);
        // #endregion

        try {
            $prepared = $imports->loadPreparedRows($this->preparedPath);
            $counts = $imports->commit(
                $prepared,
                $this->actions,
                false,
                $this->defaultDuplicateAction,
                $this->listOptions,
                $this->userId,
            );

            $archived = false;
            if (Storage::exists($this->uploadPath)) {
                try {
                    $attachments->storeFromAbsolutePath(
                        Storage::path($this->uploadPath),
                        $this->originalFilename,
                        [
                            'title' => 'Asset import: '.$this->originalFilename,
                            'category' => FileCategory::Import->value,
                            'note' => 'Archived from a successful asset import.',
                        ],
                    );
                    $archived = true;
                } catch (Throwable $e) {
                    report($e);
                }
                Storage::delete($this->uploadPath);
            }

            $imports->forgetPreparedRows($this->preparedPath);

            // #region agent log
            $log('commit_bg_done', [
                'created' => $counts['created'],
                'updated' => $counts['updated'],
                'replaced' => $counts['replaced'],
                'skipped' => $counts['skipped'],
                'archived' => $archived,
            ]);
            // #endregion
        } catch (Throwable $e) {
            // #region agent log
            $log('commit_bg_failed', [
                'exception' => $e::class,
                'error' => mb_substr($e->getMessage(), 0, 240),
            ]);
            // #endregion
            report($e);
        }
    }
}
