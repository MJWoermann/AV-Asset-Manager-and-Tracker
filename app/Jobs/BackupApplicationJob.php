<?php

namespace App\Jobs;

use App\Models\StorageOperation;
use App\Services\AppBackupService;
use App\Support\PerformanceLimits;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
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

        // #region agent log
        StorageOperation::agentDebugLog('A', 'BackupApplicationJob.php:handle', 'job handle start', [
            'operationId' => $this->operationId,
            'existing_status' => $operation?->status?->value,
            'disk_set' => $this->disk !== null,
            'keep' => $this->keep,
            'pid' => getmypid(),
            'queued_jobs' => self::queuedBackupJobs(),
        ]);
        // #endregion

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
        $operation = StorageOperation::findForJob(isset($this->operationId) ? $this->operationId : null);

        // #region agent log
        StorageOperation::agentDebugLog('A', 'BackupApplicationJob.php:failed', 'job failed callback', [
            'operationId' => $this->operationId,
            'existing_status' => $operation?->status?->value,
            'exception' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
            'pid' => getmypid(),
            'queued_jobs' => self::queuedBackupJobs(),
        ]);
        // #endregion

        $operation?->markFailed($exception?->getMessage() ?? 'Backup failed.');
    }

    /**
     * @return list<array{id: int, attempts: int, reserved_at: int|null, available_at: int}>
     */
    private static function queuedBackupJobs(): array
    {
        // #region agent log
        try {
            return DB::table('jobs')
                ->where('payload', 'like', '%BackupApplicationJob%')
                ->orderBy('id')
                ->limit(8)
                ->get(['id', 'attempts', 'reserved_at', 'available_at'])
                ->map(fn (object $job): array => [
                    'id' => (int) $job->id,
                    'attempts' => (int) $job->attempts,
                    'reserved_at' => $job->reserved_at !== null ? (int) $job->reserved_at : null,
                    'available_at' => (int) $job->available_at,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
        // #endregion
    }
}
