<?php

namespace App\Console\Commands;

use App\Services\AppBackupService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class BackupApplicationCommand extends Command
{
    protected $signature = 'app:backup
                            {--disk= : Backup target disk (defaults to BACKUP_DISK)}
                            {--keep= : Keep only the newest N backups on that disk}';

    protected $description = 'Write a dated database + config + attachments backup to a remote disk';

    public function handle(AppBackupService $backups): int
    {
        $disk = $this->option('disk') ? (string) $this->option('disk') : null;
        $keep = $this->option('keep') !== null ? (int) $this->option('keep') : null;

        try {
            $result = $backups->backup($disk, $keep);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup written to [{$result['disk']}] at {$result['path']}");

        return self::SUCCESS;
    }
}
