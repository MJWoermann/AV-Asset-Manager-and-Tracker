<?php

namespace App\Jobs;

use App\Models\StorageOperation;
use App\Services\AppBackupService;
use App\Support\PerformanceLimits;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupApplicationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3900;

    public function __construct(
        public ?string $disk = null,
        public ?int $keep = null,
        public ?int $operationId = null,
    ) {
        $this->keep ??= PerformanceLimits::int('backup_default_keep');
    }

    public function uniqueId(): string
    {
        return 'app-backup:'.($this->disk ?: 'default');
    }

    public function handle(AppBackupService $backups): void
    {
        $operation = StorageOperation::findForJob(isset($this->operationId) ? $this->operationId : null);
        $operation?->markRunning();

        try {
            $result = $backups->backup($this->disk, $this->keep);
        } catch (Throwable $exception) {
            $operation?->markFailed($exception->getMessage());

            throw $exception;
        }

        $operation?->markDone("[{$result['disk']}] {$result['path']}");

        Log::info('Application backup job finished.', [
            'disk' => $result['disk'],
            'path' => $result['path'],
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        StorageOperation::findForJob(isset($this->operationId) ? $this->operationId : null)
            ?->markFailed($exception?->getMessage() ?? 'Backup failed.');
    }
}
