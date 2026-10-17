<?php

namespace App\Jobs;

use App\Services\AppBackupService;
use App\Support\PerformanceLimits;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BackupApplicationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public int $uniqueFor = 3900;

    public function __construct(
        public ?string $disk = null,
        public ?int $keep = null,
    ) {
        $this->keep ??= PerformanceLimits::int('backup_default_keep');
    }

    public function uniqueId(): string
    {
        return 'app-backup:'.($this->disk ?: 'default');
    }

    public function handle(AppBackupService $backups): void
    {
        $result = $backups->backup($this->disk, $this->keep);

        Log::info('Application backup job finished.', [
            'disk' => $result['disk'],
            'path' => $result['path'],
        ]);
    }
}
