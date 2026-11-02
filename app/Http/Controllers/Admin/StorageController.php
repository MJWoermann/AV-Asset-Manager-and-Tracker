<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BackupApplicationJob;
use App\Jobs\MigrateAttachmentDiskJob;
use App\Jobs\RestoreApplicationJob;
use App\Models\Setting;
use App\Models\StorageOperation;
use App\Services\AppBackupService;
use App\Services\AttachmentDiskMigrator;
use App\Support\PerformanceLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class StorageController extends Controller
{
    public function edit(): View
    {
        $allowed = config('attachments.allowed_disks', ['local', 'qnap', 'sharepoint']);

        return view('admin.storage.edit', [
            'attachmentsDisk' => (string) config('attachments.disk', 'local'),
            'backupDisk' => (string) (config('attachments.backup_disk') ?: ''),
            'allowedDisks' => $allowed,
            'qnapDriver' => (string) config('filesystems.disks.qnap.driver', 'local'),
            'lastRun' => Setting::getValue(AppBackupService::LAST_RUN_KEY),
            'backupDefaultKeep' => PerformanceLimits::int('backup_default_keep'),
            'backupForceQueue' => PerformanceLimits::bool('backup_force_queue'),
            'migrateForceQueue' => PerformanceLimits::bool('migrate_force_queue'),
            'operations' => StorageOperation::query()->with('user:id,name')->latest('id')->limit(30)->get(),
        ]);
    }

    public function migrate(Request $request, AttachmentDiskMigrator $migrator): RedirectResponse
    {
        $allowed = config('attachments.allowed_disks', ['local', 'qnap', 'sharepoint']);

        $data = $request->validate([
            'from' => ['required', 'string', Rule::in($allowed)],
            'to' => ['required', 'string', Rule::in($allowed), 'different:from'],
            'dry_run' => ['sometimes', 'boolean'],
            'delete_source' => ['sometimes', 'boolean'],
            'queue' => ['sometimes', 'boolean'],
        ]);

        $dryRun = $request->boolean('dry_run');
        $deleteSource = $request->boolean('delete_source');
        $forceQueue = PerformanceLimits::bool('migrate_force_queue');
        $queue = $forceQueue || $request->boolean('queue');
        $operation = StorageOperation::open(
            StorageOperation::migrateCommand($data['from'], $data['to'], $dryRun, $deleteSource, $queue),
            $queue,
            $request->user()?->id,
        );

        if ($queue) {
            MigrateAttachmentDiskJob::dispatch($data['from'], $data['to'], $deleteSource, $dryRun, $operation->id);

            return redirect()
                ->route('admin.storage.edit')
                ->with('status', 'Attachment migration queued.');
        }

        try {
            $result = $migrator->migrate($data['from'], $data['to'], $deleteSource, $dryRun);
        } catch (Throwable $exception) {
            $operation->markFailed($exception->getMessage());

            throw $exception;
        }

        $summary = sprintf(
            'Migration %s: migrated=%d failed=%d',
            $result['dry_run'] ? 'dry-run' : 'complete',
            $result['migrated'],
            $result['failed'],
        );

        if ($result['failed'] > 0) {
            $operation->markFailed($summary);
        } else {
            $operation->markDone($summary);
        }

        return redirect()
            ->route('admin.storage.edit')
            ->with('status', $summary);
    }

    public function backup(Request $request, AppBackupService $backups): RedirectResponse
    {
        $allowed = config('attachments.allowed_disks', ['local', 'qnap', 'sharepoint']);
        $defaultKeep = PerformanceLimits::int('backup_default_keep');

        $request->merge([
            'disk' => $request->filled('disk') ? $request->input('disk') : null,
            'keep' => $request->filled('keep') ? $request->input('keep') : $defaultKeep,
        ]);

        $data = $request->validate([
            'disk' => ['nullable', 'string', Rule::in($allowed)],
            'keep' => ['required', 'integer', 'min:1', 'max:100'],
            'queue' => ['sometimes', 'boolean'],
        ]);

        $disk = $data['disk'] ?? null;
        $keep = (int) $data['keep'];
        $forceQueue = PerformanceLimits::bool('backup_force_queue');
        $queue = $forceQueue || $request->boolean('queue');
        $operation = StorageOperation::open(
            StorageOperation::backupCommand($disk, $keep, $queue),
            $queue,
            $request->user()?->id,
        );

        // #region agent log
        StorageOperation::agentDebugLog('D', 'StorageController.php:backup', 'admin backup opened new row', [
            'operationId' => $operation->id,
            'queue' => $queue,
            'user_id' => $request->user()?->id,
            'command' => $operation->command,
            'disk_set' => $disk !== null,
        ]);
        // #endregion

        if ($queue) {
            BackupApplicationJob::dispatch($disk, $keep, $operation->id);

            return redirect()
                ->route('admin.storage.edit')
                ->with('status', 'Backup queued.');
        }

        try {
            $result = $backups->backup($disk, $keep);
        } catch (Throwable $exception) {
            $operation->markFailed($exception->getMessage());

            throw $exception;
        }

        $operation->markDone("[{$result['disk']}] {$result['path']}");

        return redirect()
            ->route('admin.storage.edit')
            ->with('status', "Backup written to [{$result['disk']}] at {$result['path']}.");
    }

    public function probe(Request $request, AppBackupService $backups): RedirectResponse
    {
        $allowed = config('attachments.allowed_disks', ['local', 'qnap', 'sharepoint']);

        $request->merge([
            'disk' => $request->filled('disk') ? $request->input('disk') : null,
        ]);

        $data = $request->validate([
            'disk' => ['nullable', 'string', Rule::in($allowed)],
        ]);

        $operation = StorageOperation::open(
            StorageOperation::probeCommand($data['disk'] ?? null),
            false,
            $request->user()?->id,
        );

        try {
            $result = $backups->probe($data['disk'] ?? null);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $operation->markFailed($exception->getMessage());

            return back()->withErrors(['test' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            $operation->markFailed($exception->getMessage());

            return back()->withErrors(['test' => 'Backup location test failed: '.$exception->getMessage()]);
        }

        $message = "Write test succeeded on [{$result['disk']}]. File: {$result['path']}.";

        if (is_string($result['absolute_path']) && $result['absolute_path'] !== '') {
            $message .= ' ('.$result['absolute_path'].')';
        }

        $operation->markDone($message);

        return back()->with('status', $message);
    }

    public function restoreLatest(Request $request, AppBackupService $backups): RedirectResponse
    {
        $allowed = config('attachments.allowed_disks', ['local', 'qnap', 'sharepoint']);

        $request->merge([
            'disk' => $request->filled('disk') ? $request->input('disk') : null,
        ]);

        $data = $request->validateWithBag('restore', [
            'disk' => ['nullable', 'string', Rule::in($allowed)],
            'password' => ['required', 'current_password'],
            'queue' => ['sometimes', 'boolean'],
        ]);

        $disk = $data['disk'] ?? null;
        $forceQueue = PerformanceLimits::bool('backup_force_queue');
        $queue = $forceQueue || $request->boolean('queue');

        try {
            $latest = $backups->latestBackup($disk);
        } catch (InvalidArgumentException $exception) {
            $this->openRestore($disk, null, $queue, $request)->markFailed($exception->getMessage());

            return back()->withErrors(['restore' => $exception->getMessage()], 'restore');
        }

        if ($latest === null) {
            $message = 'No backup found to restore.';
            $this->openRestore($disk, null, $queue, $request)->markFailed($message);

            return back()->withErrors(['restore' => $message], 'restore');
        }

        $operation = $this->openRestore($latest['disk'], $latest['path'], $queue, $request);

        if ($queue) {
            RestoreApplicationJob::dispatch($latest['path'], $latest['disk'], $operation->id);

            return redirect()
                ->route('admin.storage.edit')
                ->with('status', "Restore of {$latest['path']} on [{$latest['disk']}] queued.");
        }

        try {
            $result = $backups->restore($latest['path'], $latest['disk']);
        } catch (Throwable $exception) {
            $operation->markFailed($exception->getMessage());

            return back()->withErrors(['restore' => $exception->getMessage()], 'restore');
        }

        $summary = sprintf(
            'Restored [%s] %s; attachments restored=%d.',
            $result['disk'],
            $result['path'],
            $result['attachments_restored'],
        );
        $operation->markDone($summary);

        return redirect()
            ->route('admin.storage.edit')
            ->with('status', $summary);
    }

    private function openRestore(?string $disk, ?string $path, bool $queue, Request $request): StorageOperation
    {
        return StorageOperation::open(
            StorageOperation::restoreCommand($disk, $path, $queue),
            $queue,
            $request->user()?->id,
        );
    }
}
