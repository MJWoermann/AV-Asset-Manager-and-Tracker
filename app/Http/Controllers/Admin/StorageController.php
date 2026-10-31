<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BackupApplicationJob;
use App\Jobs\MigrateAttachmentDiskJob;
use App\Models\Setting;
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

        if ($queue) {
            MigrateAttachmentDiskJob::dispatch($data['from'], $data['to'], $deleteSource, $dryRun);

            return redirect()
                ->route('admin.storage.edit')
                ->with('status', 'Attachment migration queued.');
        }

        $result = $migrator->migrate($data['from'], $data['to'], $deleteSource, $dryRun);

        return redirect()
            ->route('admin.storage.edit')
            ->with('status', sprintf(
                'Migration %s: migrated=%d failed=%d',
                $result['dry_run'] ? 'dry-run' : 'complete',
                $result['migrated'],
                $result['failed'],
            ));
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

        if ($queue) {
            BackupApplicationJob::dispatch($disk, $keep);

            return redirect()
                ->route('admin.storage.edit')
                ->with('status', 'Backup queued.');
        }

        $result = $backups->backup($disk, $keep);

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

        try {
            $result = $backups->probe($data['disk'] ?? null);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['test' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            return back()->withErrors(['test' => 'Backup location test failed: '.$exception->getMessage()]);
        }

        $message = "Write test succeeded on [{$result['disk']}]. File: {$result['path']}.";

        if (is_string($result['absolute_path']) && $result['absolute_path'] !== '') {
            $message .= ' ('.$result['absolute_path'].')';
        }

        return back()->with('status', $message);
    }
}
