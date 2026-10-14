<?php

namespace App\Services;

use App\Enums\FileSourceType;
use App\Models\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AttachmentDiskMigrator
{
    public function __construct(private FileAttachmentService $attachments) {}

    /**
     * @return array{migrated: int, skipped: int, failed: int, dry_run: bool}
     */
    public function migrate(string $from, string $to, bool $deleteSource = false, bool $dryRun = false): array
    {
        $this->assertAllowedDisk($from);
        $this->assertAllowedDisk($to);

        if ($from === $to) {
            throw new InvalidArgumentException('Source and target disks must differ.');
        }

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        $query = File::query()
            ->where('source_type', FileSourceType::Upload)
            ->where('disk', $from)
            ->whereNotNull('path')
            ->orderBy('id');

        foreach ($query->cursor() as $file) {
            try {
                $this->migrateFile($file, $from, $to, $deleteSource, $dryRun);
                $migrated++;
            } catch (Throwable $exception) {
                $failed++;
                Log::error('Attachment disk migration failed.', [
                    'file_id' => $file->id,
                    'from' => $from,
                    'to' => $to,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'migrated' => $migrated,
            'skipped' => $skipped,
            'failed' => $failed,
            'dry_run' => $dryRun,
        ];
    }

    protected function migrateFile(File $file, string $from, string $to, bool $deleteSource, bool $dryRun): void
    {
        $paths = array_values(array_filter([
            $file->path,
            $file->thumbnail_path,
        ]));

        foreach ($paths as $path) {
            $this->streamCopy($from, $to, $path, $dryRun);
        }

        if ($dryRun) {
            return;
        }

        $file->update(['disk' => $to]);

        if ($deleteSource) {
            foreach ($paths as $path) {
                if (Storage::disk($from)->exists($path)) {
                    Storage::disk($from)->delete($path);
                }
            }
        }
    }

    protected function streamCopy(string $from, string $to, string $path, bool $dryRun): void
    {
        $source = Storage::disk($from);

        if (! $source->exists($path)) {
            throw new RuntimeException("Missing source blob [{$path}] on disk [{$from}].");
        }

        $sourceSize = $source->size($path);

        if ($dryRun) {
            return;
        }

        $read = $this->attachments->readStream($from, $path);

        try {
            $written = Storage::disk($to)->writeStream($path, $read);

            if ($written === false) {
                throw new RuntimeException("Failed writing [{$path}] to disk [{$to}].");
            }
        } finally {
            if (is_resource($read)) {
                fclose($read);
            }
        }

        $targetSize = Storage::disk($to)->size($path);

        if ($targetSize !== $sourceSize) {
            Storage::disk($to)->delete($path);

            throw new RuntimeException(
                "Size mismatch after copy of [{$path}] ({$sourceSize} → {$targetSize})."
            );
        }

        $sourceHash = $this->hashIfReadable($from, $path);
        $targetHash = $this->hashIfReadable($to, $path);

        if ($sourceHash !== null && $targetHash !== null && $sourceHash !== $targetHash) {
            Storage::disk($to)->delete($path);

            throw new RuntimeException("Content hash mismatch after copy of [{$path}].");
        }
    }

    protected function hashIfReadable(string $disk, string $path): ?string
    {
        try {
            $stream = $this->attachments->readStream($disk, $path);
        } catch (Throwable) {
            return null;
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function assertAllowedDisk(string $disk): void
    {
        $allowed = config('attachments.allowed_disks', ['local', 'qnap', 'sharepoint']);

        if (! in_array($disk, $allowed, true)) {
            throw new InvalidArgumentException(
                "Disk [{$disk}] is not allowed. Use one of: ".implode(', ', $allowed)
            );
        }
    }
}
