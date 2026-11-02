<?php

namespace App\Console\Commands;

use App\Models\StorageOperation;
use App\Services\AppBackupService;
use App\Support\PerformanceLimits;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class BackupApplicationCommand extends Command
{
    protected $signature = 'app:backup
                            {--disk= : Backup target disk (defaults to BACKUP_DISK)}
                            {--keep= : Keep only the newest N backups (defaults to performance limit backup_default_keep)}';

    protected $description = 'Write a dated database + config + attachments backup to a remote disk';

    public function handle(AppBackupService $backups): int
    {
        $disk = $this->option('disk') ? (string) $this->option('disk') : null;
        $keep = $this->option('keep') !== null && $this->option('keep') !== ''
            ? (int) $this->option('keep')
            : null;

        $keepForLog = $keep ?? PerformanceLimits::int('backup_default_keep');
        $operation = StorageOperation::open(
            StorageOperation::backupCommand($disk, $keepForLog, false),
            false,
            null,
        );

        // #region agent log
        StorageOperation::agentDebugLog('C', 'BackupApplicationCommand.php:handle', 'cli backup opened new row', [
            'operationId' => $operation->id,
            'command' => $operation->command,
            'argv' => array_slice($_SERVER['argv'] ?? [], 0, 6),
            'pid' => getmypid(),
        ]);
        // #endregion

        try {
            $result = $backups->backup($disk, $keep);
        } catch (InvalidArgumentException $exception) {
            $operation->markFailed($exception->getMessage());
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $operation->markFailed($exception->getMessage());
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $operation->markDone("[{$result['disk']}] {$result['path']}");
        $this->info("Backup written to [{$result['disk']}] at {$result['path']}");

        return self::SUCCESS;
    }
}
