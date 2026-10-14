<?php

namespace App\Jobs;

use App\Services\AppBackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BackupApplicationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public ?string $disk = null,
        public ?int $keep = null,
    ) {}

    public function handle(AppBackupService $backups): void
    {
        $result = $backups->backup($this->disk, $this->keep);

        Log::info('Application backup job finished.', [
            'disk' => $result['disk'],
            'path' => $result['path'],
        ]);
    }
}
