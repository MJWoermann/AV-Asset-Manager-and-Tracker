<?php

namespace App\Models;

use App\Enums\StorageOperationStatus;
use Database\Factories\StorageOperationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        $this->update([
            'status' => StorageOperationStatus::Running,
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    public function markDone(?string $detail = null): void
    {
        $this->update([
            'status' => StorageOperationStatus::Done,
            'detail' => $detail,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(string $detail): void
    {
        if ($this->status === StorageOperationStatus::Failed) {
            return;
        }

        $this->update([
            'status' => StorageOperationStatus::Failed,
            'detail' => Str::limit($detail, 2000, ''),
            'finished_at' => $this->finished_at ?? now(),
        ]);
    }
}
