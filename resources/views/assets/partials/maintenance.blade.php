@php
    use App\Enums\AssetStatus;
    use App\Enums\FileCategory;
    use App\Enums\MaintenanceLogType;

    $types = $maintenanceLogTypes ?? MaintenanceLogType::options();
    $statuses = $maintenanceStatuses ?? AssetStatus::options();
    $logs = $maintenanceLogs ?? collect();
    $canDelete = $canDeleteMaintenanceLogs ?? false;
    $initialType = old('type', MaintenanceLogType::Note->value);
@endphp

<div class="space-y-8" x-data="{ type: @js($initialType) }">
    @if (session('status'))
        <p class="text-sm text-brand">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="text-sm text-red-600 space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="space-y-3">
        <h3 class="font-semibold text-lg">Log entry</h3>

        <form
            method="POST"
            action="{{ route('assets.maintenance-logs.store', $asset) }}"
            enctype="multipart/form-data"
            class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-4"
        >
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="maintenance_type" value="Type" />
                    <select
                        id="maintenance_type"
                        name="type"
                        x-model="type"
                        class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white focus:border-brand focus:ring-brand"
                        required
                    >
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', MaintenanceLogType::Note->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="maintenance_occurred_at" value="Occurred at" />
                    <x-text-input
                        id="maintenance_occurred_at"
                        name="occurred_at"
                        type="datetime-local"
                        class="mt-1 block w-full"
                        :value="old('occurred_at', now()->format('Y-m-d\TH:i'))"
                    />
                </div>
            </div>

            <div>
                <x-input-label for="maintenance_summary" value="Summary" />
                <x-text-input
                    id="maintenance_summary"
                    name="summary"
                    class="mt-1 block w-full"
                    :value="old('summary')"
                    required
                    maxlength="255"
                />
            </div>

            <div>
                <x-input-label for="maintenance_body" value="Details" />
                <textarea
                    id="maintenance_body"
                    name="body"
                    rows="3"
                    class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white focus:border-brand focus:ring-brand"
                >{{ old('body') }}</textarea>
            </div>

            <div x-show="type === 'fault'" x-cloak>
                <x-input-label for="maintenance_status" value="Update asset status (optional)" />
                <select
                    id="maintenance_status"
                    class="mt-1 block w-full border-brand-silver dark:border-brand-charcoal dark:bg-brand-slate dark:text-white focus:border-brand focus:ring-brand"
                    :name="type === 'fault' ? 'status' : null"
                >
                    <option value="">No status change</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Only Fault entries can change status.</p>
            </div>

            <div>
                <x-input-label for="maintenance_files" value="Attachments (optional)" />
                <input
                    id="maintenance_files"
                    name="files[]"
                    type="file"
                    multiple
                    class="mt-1 block w-full text-sm"
                />
            </div>

            <div>
                <x-primary-button>Add entry</x-primary-button>
            </div>
        </form>
    </section>

    <section class="space-y-3">
        <h3 class="font-semibold text-lg">History</h3>

        @php
            $maintenanceFiltersActive = \App\Support\AssetMaintenanceFilters::hasActiveFilters(request());
            $userOptions = $maintenanceUserOptions ?? collect();
        @endphp

        <form
            method="GET"
            action="{{ route('assets.show', $asset) }}"
            class="flex flex-col sm:flex-row flex-wrap gap-3 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
        >
            <input type="hidden" name="tab" value="maintenance">
            <div>
                <x-input-label for="maintenance_date_from" value="From" />
                <x-text-input id="maintenance_date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from')" />
            </div>
            <div>
                <x-input-label for="maintenance_date_to" value="To" />
                <x-text-input id="maintenance_date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to')" />
            </div>
            <div class="min-w-[10rem]">
                <x-input-label for="maintenance_filter_type" value="Type" />
                <select id="maintenance_filter_type" name="log_type" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="">All</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('log_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[10rem]">
                <x-input-label for="maintenance_filter_user_id" value="User" />
                <select id="maintenance_filter_user_id" name="user_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="">All</option>
                    @foreach($userOptions as $option)
                        <option value="{{ $option['value'] }}" @selected((string) request('user_id') === (string) $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 flex-wrap">
                <x-primary-button>Filter</x-primary-button>
                @if($maintenanceFiltersActive)
                    <a href="{{ route('assets.show', [$asset, 'tab' => 'maintenance']) }}" class="px-4 py-2 border border-brand-charcoal dark:border-white text-sm">Clear</a>
                @endif
            </div>
        </form>

        <div class="space-y-4">
            @forelse($logs as $log)
                @php
                    /** @var \App\Models\AssetMaintenanceLog $log */
                    $fileRows = $log->files->map(fn ($file) => [
                        'file' => $file,
                        'inherited' => false,
                        'link_id' => (int) $file->pivot->id,
                        'note' => $file->pivot->note,
                    ])->all();
                    $statusBefore = $log->status_before ? AssetStatus::tryFrom($log->status_before) : null;
                    $statusAfter = $log->status_after ? AssetStatus::tryFrom($log->status_after) : null;
                @endphp
                <article class="bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4 space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium border border-brand text-brand">
                                    {{ $log->type->label() }}
                                </span>
                                <span class="text-sm text-brand-charcoal dark:text-brand-silver">
                                    {{ $log->occurred_at?->format('Y-m-d H:i') }}
                                </span>
                                @if($statusBefore && $statusAfter)
                                    <span class="inline-flex px-2 py-0.5 text-xs border border-brand-silver dark:border-brand-charcoal">
                                        {{ $statusBefore->label() }} → {{ $statusAfter->label() }}
                                    </span>
                                @endif
                            </div>
                            <h4 class="font-medium">{{ $log->summary }}</h4>
                            <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                                Logged by {{ $log->user?->name ?? 'Unknown' }}
                            </p>
                        </div>
                        @if($canDelete)
                            <form
                                method="POST"
                                action="{{ route('assets.maintenance-logs.destroy', [$asset, $log]) }}"
                                onsubmit="return confirm('Delete this maintenance entry?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600">Delete</button>
                            </form>
                        @endif
                    </div>

                    @if($log->body)
                        <p class="text-sm whitespace-pre-wrap">{{ $log->body }}</p>
                    @endif

                    <div class="pt-2 border-t border-brand-silver dark:border-brand-charcoal">
                        <x-file-attachments
                            linkable-type="maintenance_log"
                            :linkable-id="$log->id"
                            :rows="$fileRows"
                            :can-mutate="true"
                            :categories="collect(FileCategory::options())->only([
                                FileCategory::Maintenance->value,
                                FileCategory::Photo->value,
                                FileCategory::Other->value,
                            ])->all()"
                        />
                    </div>
                </article>
            @empty
                <p class="text-sm text-brand-charcoal dark:text-brand-silver">
                    {{ $maintenanceFiltersActive ? 'No maintenance entries match these filters.' : 'No maintenance entries yet.' }}
                </p>
            @endforelse
        </div>
    </section>
</div>
