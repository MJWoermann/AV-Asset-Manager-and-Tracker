<?php

namespace App\Models;

use App\Enums\StorageOperationStatus;
use Database\Factories\StorageOperationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Throwable;

class StorageOperation extends Model
{
    /** @use HasFactory<StorageOperationFactory> */
    use HasFactory;

    protected $fillable = [
        'command',
        'status',
        'detail',
        'user_id',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StorageOperationStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function backupCommand(?string $disk, int $keep, bool $queued): string
    {
        $parts = ['app:backup'];

        if (filled($disk)) {
            $parts[] = '--disk='.$disk;
        }

        $parts[] = '--keep='.$keep;

        if ($queued) {
            $parts[] = '--queue';
        }

        return implode(' ', $parts);
    }

    public static function migrateCommand(string $from, string $to, bool $dryRun, bool $deleteSource, bool $queued): string
    {
        $parts = ['attachments:migrate-disk', $from, $to];

        if ($dryRun) {
            $parts[] = '--dry-run';
        }

        if ($deleteSource) {
            $parts[] = '--delete-source';
        }

        if ($queued) {
            $parts[] = '--queue';
        }

        return implode(' ', $parts);
    }

    public static function restoreCommand(?string $disk, ?string $path, bool $queued): string
    {
        $parts = ['app:restore'];

        if (filled($path)) {
            $parts[] = $path;
        }

        if (filled($disk)) {
            $parts[] = '--disk='.$disk;
        }

        $parts[] = '--force';

        if ($queued) {
            $parts[] = '--queue';
        }

        return implode(' ', $parts);
    }

    public static function probeCommand(?string $disk): string
    {
        $parts = ['storage:test-backup'];

        if (filled($disk)) {
            $parts[] = '--disk='.$disk;
        }

        return implode(' ', $parts);
    }

    public static function open(string $command, bool $queued, ?int $userId): self
    {
        return static::query()->create([
            'command' => $command,
            'status' => $queued ? StorageOperationStatus::Queued : StorageOperationStatus::Running,
            'user_id' => $userId,
            'started_at' => $queued ? null : now(),
        ]);
    }

    public static function findForJob(?int $id): ?self
    {
        if ($id === null) {
            return null;
        }

        return static::query()->find($id);
    }

    public function markRunning(): void
    {
        $from = $this->status->value;
        $this->update([
            'status' => StorageOperationStatus::Running,
            'started_at' => $this->started_at ?? now(),
        ]);

        // #region agent log
        self::agentDebugLog('B', 'StorageOperation.php:markRunning', 'status transition', [
            'id' => $this->id,
            'from' => $from,
            'to' => 'running',
            'command' => $this->command,
            'user_id' => $this->user_id,
        ]);
        // #endregion
    }

    public function markDone(?string $detail = null): void
    {
        $from = $this->status->value;
        $this->update([
            'status' => StorageOperationStatus::Done,
            'detail' => $detail,
            'finished_at' => now(),
        ]);

        // #region agent log
        self::agentDebugLog('B', 'StorageOperation.php:markDone', 'status transition', [
            'id' => $this->id,
            'from' => $from,
            'to' => 'done',
            'command' => $this->command,
            'user_id' => $this->user_id,
        ]);
        // #endregion
    }

    public function markFailed(string $detail): void
    {
        $from = $this->status->value;

        if ($this->status === StorageOperationStatus::Failed) {
            // #region agent log
            self::agentDebugLog('B', 'StorageOperation.php:markFailed', 'markFailed skipped already failed', [
                'id' => $this->id,
                'from' => $from,
                'detail_prefix' => mb_substr($detail, 0, 160),
            ]);
            // #endregion

            return;
        }

        $this->update([
            'status' => StorageOperationStatus::Failed,
            'detail' => Str::limit($detail, 2000, ''),
            'finished_at' => $this->finished_at ?? now(),
        ]);

        // #region agent log
        self::agentDebugLog('B', 'StorageOperation.php:markFailed', 'status transition', [
            'id' => $this->id,
            'from' => $from,
            'to' => 'failed',
            'command' => $this->command,
            'user_id' => $this->user_id,
            'detail_prefix' => mb_substr($detail, 0, 160),
        ]);
        // #endregion
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function agentDebugLog(string $hypothesisId, string $location, string $message, array $data = []): void
    {
        // #region agent log
        try {
            $line = json_encode([
                'sessionId' => 'a28699',
                'runId' => 'pre-fix',
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES);

            if (is_string($line)) {
                file_put_contents(base_path('debug-a28699.log'), $line.PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        } catch (Throwable) {
        }
        // #endregion
    }
}
