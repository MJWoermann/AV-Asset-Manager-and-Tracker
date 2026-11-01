<?php

namespace App\Jobs;

use App\Models\StorageOperation;
use App\Services\AttachmentDiskMigrator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateAttachmentDiskJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3900;

    public function __construct(
        public string $from,
        public string $to,
        public bool $deleteSource = false,
        public bool $dryRun = false,
        public ?int $operationId = null,
    ) {}

    public function uniqueId(): string
    {
        return 'attachment-migrate:'.$this->from.':'.$this->to.':'.($this->dryRun ? 'dry' : 'live');
    }

    public function handle(AttachmentDiskMigrator $migrator): void
    {
        $operation = StorageOperation::findForJob(isset($this->operationId) ? $this->operationId : null);
        $operation?->markRunning();

        try {
            $result = $migrator->migrate($this->from, $this->to, $this->deleteSource, $this->dryRun);
        } catch (Throwable $exception) {
            $operation?->markFailed($exception->getMessage());

            throw $exception;
        }

        $detail = sprintf(
            '%s: migrated=%d failed=%d',
            $result['dry_run'] ? 'dry-run' : 'complete',
            $result['migrated'],
            $result['failed'],
        );

        if (($result['failed'] ?? 0) > 0) {
            $operation?->markFailed($detail);
        } else {
            $operation?->markDone($detail);
        }

        Log::info('Attachment disk migration job finished.', $result + [
            'from' => $this->from,
            'to' => $this->to,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        StorageOperation::findForJob(isset($this->operationId) ? $this->operationId : null)
            ?->markFailed($exception?->getMessage() ?? 'Attachment migration failed.');
    }
}
