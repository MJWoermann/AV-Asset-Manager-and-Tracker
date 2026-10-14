<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Storage</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-8">
        <section class="bg-white dark:bg-brand-slate border p-4 space-y-2">
            <h3 class="font-medium">Current disks</h3>
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-brand-charcoal dark:text-brand-silver">ATTACHMENTS_DISK</dt>
                    <dd class="font-mono">{{ $attachmentsDisk }}</dd>
                </div>
                <div>
                    <dt class="text-brand-charcoal dark:text-brand-silver">BACKUP_DISK</dt>
                    <dd class="font-mono">{{ $backupDisk !== '' ? $backupDisk : '— not set —' }}</dd>
                </div>
                <div>
                    <dt class="text-brand-charcoal dark:text-brand-silver">QNAP driver</dt>
                    <dd class="font-mono">{{ $qnapDriver }}</dd>
                </div>
            </dl>
            @if(is_array($lastRun))
                <p class="text-sm text-brand-charcoal dark:text-brand-silver pt-2">
                    Last backup: <span class="font-mono">{{ $lastRun['path'] ?? '—' }}</span>
                    on <span class="font-mono">{{ $lastRun['disk'] ?? '—' }}</span>
                    @if(! empty($lastRun['finished_at']))
                        ({{ $lastRun['finished_at'] }})
                    @endif
                </p>
            @else
                <p class="text-sm text-brand-charcoal dark:text-brand-silver pt-2">No backup recorded yet.</p>
            @endif
        </section>

        <section class="bg-white dark:bg-brand-slate border p-4">
            <h3 class="font-medium mb-3">Migrate attachments</h3>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver mb-4">
                Stream-copy upload blobs from one disk to another and update <code class="text-xs">files.disk</code>.
                Prefer a dry-run first. Large libraries should use the queue.
            </p>
            <form method="POST" action="{{ route('admin.storage.migrate') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="from" value="From" />
                        <select id="from" name="from" class="mt-1 block w-full border-brand-charcoal/40 dark:bg-brand-charcoal dark:text-white">
                            @foreach($allowedDisks as $disk)
                                <option value="{{ $disk }}" @selected(old('from', $attachmentsDisk) === $disk)>{{ $disk }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('from')" />
                    </div>
                    <div>
                        <x-input-label for="to" value="To" />
                        <select id="to" name="to" class="mt-1 block w-full border-brand-charcoal/40 dark:bg-brand-charcoal dark:text-white">
                            @foreach($allowedDisks as $disk)
                                <option value="{{ $disk }}" @selected(old('to') === $disk)>{{ $disk }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('to')" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="dry_run" value="1" class="rounded border-brand-charcoal/40 text-brand focus:ring-brand" @checked(old('dry_run', true))>
                        Dry-run
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="delete_source" value="1" class="rounded border-brand-charcoal/40 text-brand focus:ring-brand" @checked(old('delete_source'))>
                        Delete source after copy
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="queue" value="1" class="rounded border-brand-charcoal/40 text-brand focus:ring-brand" @checked(old('queue', true))>
                        Run on queue
                    </label>
                </div>
                <x-primary-button>Migrate</x-primary-button>
            </form>
        </section>

        <section class="bg-white dark:bg-brand-slate border p-4">
            <h3 class="font-medium mb-3">Backup now</h3>
            <p class="text-sm text-brand-charcoal dark:text-brand-silver mb-4">
                Writes a dated folder with database dump (or table JSON), config packs, file index, and attachment blobs.
                Does not include <code class="text-xs">.env</code> or Graph/QNAP secrets.
            </p>
            <form method="POST" action="{{ route('admin.storage.backup') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="disk" value="Backup disk" />
                        <select id="disk" name="disk" class="mt-1 block w-full border-brand-charcoal/40 dark:bg-brand-charcoal dark:text-white">
                            <option value="">Use BACKUP_DISK ({{ $backupDisk !== '' ? $backupDisk : 'unset' }})</option>
                            @foreach($allowedDisks as $disk)
                                <option value="{{ $disk }}" @selected(old('disk') === $disk)>{{ $disk }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('disk')" />
                    </div>
                    <div>
                        <x-input-label for="keep" value="Keep newest (optional)" />
                        <x-text-input id="keep" name="keep" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('keep')" />
                        <x-input-error class="mt-2" :messages="$errors->get('keep')" />
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="queue" value="1" class="rounded border-brand-charcoal/40 text-brand focus:ring-brand" @checked(old('queue', true))>
                    Run on queue
                </label>
                <div>
                    <x-primary-button>Backup now</x-primary-button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
