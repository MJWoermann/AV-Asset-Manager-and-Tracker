<?php

namespace App\Jobs;

use App\Services\AttachmentDiskMigrator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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
    ) {}

    public function uniqueId(): string
    {
        return 'attachment-migrate:'.$this->from.':'.$this->to.':'.($this->dryRun ? 'dry' : 'live');
    }

    public function handle(AttachmentDiskMigrator $migrator): void
    {
        $result = $migrator->migrate($this->from, $this->to, $this->deleteSource, $this->dryRun);

        Log::info('Attachment disk migration job finished.', $result + [
            'from' => $this->from,
            'to' => $this->to,
        ]);
    }
}
