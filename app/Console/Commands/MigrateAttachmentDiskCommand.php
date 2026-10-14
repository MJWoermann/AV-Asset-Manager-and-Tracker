<?php

namespace App\Console\Commands;

use App\Services\AttachmentDiskMigrator;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class MigrateAttachmentDiskCommand extends Command
{
    protected $signature = 'attachments:migrate-disk
                            {from : Source disk (local|qnap|sharepoint)}
                            {to : Target disk (local|qnap|sharepoint)}
                            {--delete-source : Delete blobs from the source disk after a successful copy}
                            {--dry-run : Validate and count without writing}';

    protected $description = 'Stream-copy uploaded attachment blobs between filesystem disks and update files.disk';

    public function handle(AttachmentDiskMigrator $migrator): int
    {
        $from = (string) $this->argument('from');
        $to = (string) $this->argument('to');
        $deleteSource = (bool) $this->option('delete-source');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $migrator->migrate($from, $to, $deleteSource, $dryRun);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Migration %s: migrated=%d failed=%d skipped=%d',
            $result['dry_run'] ? 'dry-run' : 'complete',
            $result['migrated'],
            $result['failed'],
            $result['skipped'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
