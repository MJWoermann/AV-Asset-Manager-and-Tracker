<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\MaintenanceLogType;
use App\Enums\NotificationEventKey;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\User;
use App\Support\EmailIntegrationSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AssetMaintenanceLogService
{
    public function __construct(private FileAttachmentService $attachments) {}

    /**
     * @param  array{
     *     type: MaintenanceLogType|string,
     *     summary: string,
     *     body?: string|null,
     *     occurred_at?: \DateTimeInterface|string|null,
     *     status?: AssetStatus|string|null,
     *     source?: string|null,
     * }  $input
     * @param  list<UploadedFile>|UploadedFile|null  $files
     */
    public function create(
        Asset $asset,
        array $input,
        ?User $user = null,
        UploadedFile|array|null $files = null,
    ): AssetMaintenanceLog {
        $type = $input['type'] instanceof MaintenanceLogType
            ? $input['type']
            : MaintenanceLogType::from((string) $input['type']);

        $uploads = $this->normalizeUploads($files);
        $source = isset($input['source']) ? (string) $input['source'] : null;

        $log = DB::transaction(function () use ($asset, $type, $input, $user, $uploads): AssetMaintenanceLog {
            $statusBefore = null;
            $statusAfter = null;

            if ($type === MaintenanceLogType::Fault && filled($input['status'] ?? null)) {
                $newStatus = $input['status'] instanceof AssetStatus
                    ? $input['status']
                    : AssetStatus::from((string) $input['status']);

                $current = $asset->status instanceof AssetStatus
                    ? $asset->status
                    : AssetStatus::from((string) $asset->status);

                if ($newStatus !== $current) {
                    $statusBefore = $current->value;
                    $statusAfter = $newStatus->value;
                    $asset->update(['status' => $newStatus]);
                }
            }

            $log = AssetMaintenanceLog::query()->create([
                'asset_id' => $asset->id,
                'user_id' => $user?->id,
                'type' => $type,
                'summary' => (string) $input['summary'],
                'body' => $this->nullableString($input['body'] ?? null),
                'occurred_at' => $input['occurred_at'] ?? now(),
                'status_before' => $statusBefore,
                'status_after' => $statusAfter,
            ]);

            foreach ($uploads as $upload) {
                $this->attachments->storeUpload($upload, [
                    'title' => pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME) ?: ('Maintenance '.$log->id),
                    'category' => FileCategory::Maintenance->value,
                ], $log);
            }

            return $log->fresh(['files', 'user']);
        });

        $this->dispatchNotifications($asset, $log, $user, $source);

        return $log;
    }

    public function delete(AssetMaintenanceLog $log): void
    {
        $log->delete();
    }

    protected function dispatchNotifications(
        Asset $asset,
        AssetMaintenanceLog $log,
        ?User $user,
        ?string $source,
    ): void {
        // Inbound email Notes must not re-trigger outbound mail (loop guard).
        if (in_array($source, ['email', 'email_outbound'], true)) {
            return;
        }

        if (! EmailIntegrationSettings::isEnabled()) {
            return;
        }

        $dispatcher = app(NotificationDispatchService::class);

        $dispatcher->dispatch(NotificationEventKey::MaintenanceLogCreated, $asset, $user, [
            'log.summary' => (string) $log->summary,
            'log.type' => $log->type instanceof MaintenanceLogType ? $log->type->value : (string) $log->type,
        ]);

        if (
            $log->type === MaintenanceLogType::Fault
            && filled($log->status_after)
            && $log->status_before !== $log->status_after
        ) {
            $dispatcher->dispatch(NotificationEventKey::MaintenanceFaultStatusChanged, $asset, $user, [
                'log.summary' => (string) $log->summary,
                'log.status_before' => (string) ($log->status_before ?? ''),
                'log.status_after' => (string) ($log->status_after ?? ''),
            ]);
        }
    }

    /**
     * @param  list<UploadedFile>|UploadedFile|null  $files
     * @return list<UploadedFile>
     */
    protected function normalizeUploads(UploadedFile|array|null $files): array
    {
        if ($files === null) {
            return [];
        }

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values(array_filter($files, fn ($file): bool => $file instanceof UploadedFile));
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
