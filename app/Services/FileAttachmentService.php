<?php

namespace App\Services;

use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\FileLink;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class FileAttachmentService
{
    /**
     * @return class-string<Model>
     */
    public function resolveLinkableClass(string $type): string
    {
        return match ($type) {
            'asset' => Asset::class,
            'location' => Location::class,
            'item_type' => ItemType::class,
            'stock_movement' => StockMovement::class,
            'maintenance_log' => AssetMaintenanceLog::class,
            default => throw new InvalidArgumentException('Unsupported linkable type.'),
        };
    }

    public function resolveLinkable(string $type, int $id): Model
    {
        $class = $this->resolveLinkableClass($type);

        return $class::query()->findOrFail($id);
    }

    public function linkableTypeKey(Model $linkable): string
    {
        return match ($linkable::class) {
            Asset::class => 'asset',
            Location::class => 'location',
            ItemType::class => 'item_type',
            StockMovement::class => 'stock_movement',
            AssetMaintenanceLog::class => 'maintenance_log',
            default => throw new InvalidArgumentException('Unsupported linkable model.'),
        };
    }

    /**
     * @param  array{title: string, category: string, note?: string|null}  $attributes
     */
    public function storeUpload(UploadedFile $upload, array $attributes, ?Model $linkable = null): File
    {
        $disk = (string) config('attachments.disk', 'local');
        $processed = $this->processUpload($upload);
        $hash = hash('sha256', $processed['contents']);

        return DB::transaction(function () use ($upload, $attributes, $linkable, $disk, $processed, $hash): File {
            $existing = File::query()->where('content_hash', $hash)->first();

            if ($existing) {
                if ($linkable) {
                    $this->link($existing, $linkable, $attributes['note'] ?? null);
                }

                return $existing;
            }

            $extension = $processed['extension'];
            $directory = 'attachments/'.now()->format('Y/m');
            $filename = Str::uuid()->toString().'.'.$extension;
            $path = $directory.'/'.$filename;

            $this->putContents($disk, $path, $processed['contents']);

            $thumbnailPath = null;
            if ($processed['thumbnail'] !== null) {
                $thumbnailPath = $directory.'/'.Str::uuid()->toString().'_thumb.jpg';
                $this->putContents($disk, $thumbnailPath, $processed['thumbnail']);
            }

            $file = File::query()->create([
                'title' => $attributes['title'],
                'category' => FileCategory::from($attributes['category']),
                'source_type' => FileSourceType::Upload,
                'disk' => $disk,
                'path' => $path,
                'content_hash' => $hash,
                'mime_type' => $processed['mime'],
                'size_bytes' => strlen($processed['contents']),
                'original_filename' => $upload->getClientOriginalName(),
                'thumbnail_path' => $thumbnailPath,
                'uploaded_by' => Auth::id(),
            ]);

            if ($linkable) {
                $this->link($file, $linkable, $attributes['note'] ?? null);
            }

            return $file;
        });
    }

    /**
     * @param  array{title: string, category: string, external_url: string, note?: string|null}  $attributes
     */
    public function storeUrl(array $attributes, ?Model $linkable = null): File
    {
        return DB::transaction(function () use ($attributes, $linkable): File {
            $file = File::query()->create([
                'title' => $attributes['title'],
                'category' => FileCategory::from($attributes['category']),
                'source_type' => FileSourceType::Url,
                'external_url' => $attributes['external_url'],
                'uploaded_by' => Auth::id(),
            ]);

            if ($linkable) {
                $this->link($file, $linkable, $attributes['note'] ?? null);
            }

            return $file;
        });
    }

    public function link(File $file, Model $linkable, ?string $note = null): FileLink
    {
        $existing = FileLink::query()
            ->where('file_id', $file->id)
            ->where('linkable_type', $linkable::class)
            ->where('linkable_id', $linkable->getKey())
            ->first();

        if ($existing) {
            if ($note !== null && $existing->note !== $note) {
                $existing->update(['note' => $note]);
            }

            return $existing->fresh();
        }

        $link = FileLink::query()->create([
            'file_id' => $file->id,
            'linkable_type' => $linkable::class,
            'linkable_id' => $linkable->getKey(),
            'note' => $note,
        ]);

        $this->auditLinkable($linkable, 'file_linked', null, [
            'file_id' => $file->id,
            'title' => $file->title,
            'category' => $file->category->value,
            'note' => $note,
        ]);

        return $link;
    }

    public function unlink(FileLink $link, bool $deleteOrphan = true): void
    {
        $file = $link->file;
        $linkable = $link->linkable;

        DB::transaction(function () use ($link, $file, $linkable, $deleteOrphan): void {
            $payload = [
                'file_id' => $file?->id,
                'title' => $file?->title,
                'category' => $file?->category?->value,
                'note' => $link->note,
            ];

            $link->delete();

            if ($linkable) {
                $this->auditLinkable($linkable, 'file_unlinked', $payload, null);
            }

            if ($deleteOrphan && $file && $file->links()->count() === 0) {
                $this->deleteFile($file);
            }
        });
    }

    public function deleteFile(File $file): void
    {
        $disk = $file->disk ?: (string) config('attachments.disk', 'local');

        if ($file->path && Storage::disk($disk)->exists($file->path)) {
            Storage::disk($disk)->delete($file->path);
        }

        if ($file->thumbnail_path && Storage::disk($disk)->exists($file->thumbnail_path)) {
            Storage::disk($disk)->delete($file->thumbnail_path);
        }

        $file->links()->delete();
        $file->delete();
    }

    public function detachAll(Model $linkable, bool $audit = false): void
    {
        $links = FileLink::query()
            ->where('linkable_type', $linkable::class)
            ->where('linkable_id', $linkable->getKey())
            ->with('file')
            ->get();

        foreach ($links as $link) {
            if ($audit) {
                $this->unlink($link, deleteOrphan: true);

                continue;
            }

            $file = $link->file;
            $link->delete();

            if ($file && $file->links()->count() === 0) {
                $this->deleteFile($file);
            }
        }
    }

    /**
     * @return list<array{file: File, inherited: bool, link_id: int|null, note: string|null}>
     */
    public function filesForAsset(Asset $asset): array
    {
        $asset->loadMissing(['files', 'itemType.files']);

        $rows = [];
        $seen = [];

        foreach ($asset->files as $file) {
            $seen[$file->id] = true;
            $rows[] = [
                'file' => $file,
                'inherited' => false,
                'link_id' => (int) $file->pivot->id,
                'note' => $file->pivot->note,
            ];
        }

        foreach ($asset->itemType?->files ?? [] as $file) {
            if (isset($seen[$file->id])) {
                continue;
            }

            $rows[] = [
                'file' => $file,
                'inherited' => true,
                'link_id' => null,
                'note' => $file->pivot->note,
            ];
        }

        usort($rows, fn (array $a, array $b): int => strcasecmp($a['file']->title, $b['file']->title));

        return $rows;
    }

    /**
     * Stream string contents to a disk (local or remote) after in-memory processing.
     */
    public function putContents(string $disk, string $path, string $contents): void
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Unable to open temporary stream for attachment upload.');
        }

        try {
            fwrite($stream, $contents);
            rewind($stream);

            $written = Storage::disk($disk)->writeStream($path, $stream);

            if ($written === false) {
                throw new RuntimeException("Unable to store attachment on disk [{$disk}].");
            }
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                "Unable to store attachment on disk [{$disk}]: {$exception->getMessage()}",
                previous: $exception,
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @return resource
     */
    public function readStream(string $disk, string $path)
    {
        try {
            $stream = Storage::disk($disk)->readStream($path);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                "Unable to read attachment from disk [{$disk}]: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if ($stream === null) {
            throw new RuntimeException("Unable to read attachment from disk [{$disk}] at [{$path}].");
        }

        return $stream;
    }

    /**
     * @return array{contents: string, mime: string, extension: string, thumbnail: string|null}
     */
    protected function processUpload(UploadedFile $upload): array
    {
        $extension = strtolower($upload->getClientOriginalExtension() ?: $upload->extension() ?: 'bin');
        $contents = file_get_contents($upload->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        $mime = $upload->getMimeType() ?: 'application/octet-stream';
        $thumbnail = null;

        if (in_array($extension, config('attachments.image_mimes', []), true) && extension_loaded('gd')) {
            $processed = $this->processImage($contents, $extension);
            $contents = $processed['contents'];
            $mime = $processed['mime'];
            $extension = $processed['extension'];
            $thumbnail = $processed['thumbnail'];
        }

        return [
            'contents' => $contents,
            'mime' => $mime,
            'extension' => $extension,
            'thumbnail' => $thumbnail,
        ];
    }

    /**
     * @return array{contents: string, mime: string, extension: string, thumbnail: string|null}
     */
    protected function processImage(string $contents, string $extension): array
    {
        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            return [
                'contents' => $contents,
                'mime' => 'image/'.$extension,
                'extension' => $extension,
                'thumbnail' => null,
            ];
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxEdge = (int) config('attachments.image_max_edge', 1920);
        $scale = max($width, $height) > $maxEdge
            ? $maxEdge / max($width, $height)
            : 1.0;

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if (in_array($extension, ['png', 'webp'], true)) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $outExtension = $extension === 'png' ? 'png' : ($extension === 'webp' && function_exists('imagewebp') ? 'webp' : 'jpg');
        $encoded = $this->encodeImage($resized, $outExtension);
        $thumbnail = $this->makeThumbnail($resized);

        imagedestroy($resized);
        imagedestroy($source);

        return [
            'contents' => $encoded,
            'mime' => match ($outExtension) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            },
            'extension' => $outExtension,
            'thumbnail' => $thumbnail,
        ];
    }

    protected function encodeImage(\GdImage $image, string $extension): string
    {
        ob_start();

        match ($extension) {
            'png' => imagepng($image, null, 6),
            'webp' => imagewebp($image, null, (int) config('attachments.webp_quality', 80)),
            default => imagejpeg($image, null, (int) config('attachments.jpeg_quality', 82)),
        };

        $data = ob_get_clean();

        if ($data === false) {
            throw new RuntimeException('Unable to encode image.');
        }

        return $data;
    }

    protected function makeThumbnail(\GdImage $image): ?string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $maxEdge = (int) config('attachments.thumbnail_max_edge', 400);
        $scale = max($width, $height) > $maxEdge
            ? $maxEdge / max($width, $height)
            : 1.0;

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($thumb, null, (int) config('attachments.jpeg_quality', 82));
        $data = ob_get_clean();
        imagedestroy($thumb);

        return $data === false ? null : $data;
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected function auditLinkable(Model $linkable, string $action, ?array $old, ?array $new): void
    {
        if (! in_array($linkable::class, [Asset::class, Location::class], true)) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => $linkable::class,
            'auditable_id' => $linkable->getKey(),
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'created_at' => now(),
        ]);
    }
}
