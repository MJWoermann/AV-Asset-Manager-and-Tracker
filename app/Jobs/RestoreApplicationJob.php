<?php

namespace App\Jobs;

use App\Models\StorageOperation;
use App\Services\AppBackupService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestoreApplicationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3900;

    public function __construct(
        public string $backupPath,
        public ?string $disk = null,
        public ?int $operationId = null,
    ) {}

    public function uniqueId(): string
    {
        return 'app-restore:'.($this->disk ?: 'default');
    }

    public function handle(AppBackupService $backups): void
    {
        $operation = StorageOperation::findForJob(isset($this->operationId) ? $this->operationId : null);
        $operation?->markRunning();

        try {
            $result = $backups->restore($this->backupPath, $this->disk);
        } catch (Throwable $exception) {
            $operation?->markFailed($exception->getMessage());

            throw $exception;
        }

        $detail = sprintf(
            '[%s] %s; attachments restored=%d',
            $result['disk'],
            $result['path'],
            $result['attachments_restored'],
        );
        $operation?->markDone($detail);

        Log::info('Application restore job finished.', [
            'disk' => $result['disk'],
            'path' => $result['path'],
            'attachments_restored' => $result['attachments_restored'],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        StorageOperation::findForJob(isset($this->operationId) ? $this->operationId : null)
            ?->markFailed($exception?->getMessage() ?? 'Restore failed.');
    }
}
