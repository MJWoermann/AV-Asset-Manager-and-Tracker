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

        try {
            $prepared = $imports->loadPreparedRows($this->preparedPath);
            $imports->commit(
                $prepared,
                $this->actions,
                false,
                $this->defaultDuplicateAction,
                $this->listOptions,
                $this->userId,
            );

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
                } catch (Throwable $e) {
                    report($e);
                }
                Storage::delete($this->uploadPath);
            }

            $imports->forgetPreparedRows($this->preparedPath);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
