<?php

namespace App\Console\Commands;

use App\Services\AppBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class RestoreApplicationCommand extends Command
{
    protected $signature = 'app:restore
                            {backup-path : Path under the backup disk, e.g. backups/2026-10-14_120000}
                            {--disk= : Backup source disk (defaults to BACKUP_DISK)}
                            {--force : Skip confirmation}';

    protected $description = 'Restore SQL/JSON dump from a backup folder and re-import attachment blobs onto ATTACHMENTS_DISK';

    public function handle(AppBackupService $backups): int
    {
        if (! $this->option('force') && ! $this->confirm('This overwrites database data from the backup. Continue?', false)) {
            $this->warn('Restore cancelled.');

            return self::SUCCESS;
        }

        $disk = $this->option('disk') ? (string) $this->option('disk') : null;

        try {
            $result = $backups->restore((string) $this->argument('backup-path'), $disk);
        } catch (InvalidArgumentException $exception) {
            Log::error('Application restore failed.', [
                'backup' => (string) $this->argument('backup-path'),
                'message' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            Log::error('Application restore failed.', [
                'backup' => (string) $this->argument('backup-path'),
                'message' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        Log::info('Application restore finished.', [
            'disk' => $result['disk'],
            'path' => $result['path'],
            'attachments_restored' => $result['attachments_restored'],
        ]);

        $this->info(sprintf(
            'Restore complete from [%s] on [%s]; attachments restored=%d',
            $result['path'],
            $result['disk'],
            $result['attachments_restored'],
        ));

        return self::SUCCESS;
    }
}
