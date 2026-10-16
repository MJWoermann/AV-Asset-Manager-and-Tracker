<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $rule->exists ? 'Edit inbound rule' : 'New inbound rule' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <x-email-tabs />

        <form method="POST" action="{{ $rule->exists ? route('admin.email.rules.update', $rule) : route('admin.email.rules.store') }}" class="space-y-4 bg-white dark:bg-brand-slate border p-4">
            @csrf
            @if($rule->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $rule->name)" required />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="priority" value="Priority (lower first)" />
                    <x-text-input id="priority" name="priority" type="number" class="mt-1 block w-full" :value="old('priority', $rule->priority)" required />
                </div>
                <div>
                    <x-input-label for="action" value="Action" />
                    <select id="action" name="action" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        @foreach($actions as $value => $label)
                            <option value="{{ $value }}" @selected(old('action', $rule->action?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="log_type" value="Log type" />
                    <select id="log_type" name="log_type" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        @foreach($logTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('log_type', $rule->log_type?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="fault_status" value="Fault status (optional)" />
                    <select id="fault_status" name="fault_status" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">No status change</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('fault_status', $rule->fault_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-3">
                <legend class="px-1 text-sm font-medium">Match conditions</legend>
                <div>
                    <x-input-label for="subject_contains" value="Subject contains" />
                    <x-text-input id="subject_contains" name="subject_contains" class="mt-1 block w-full" :value="old('subject_contains', $subjectContains)" />
                </div>
                <div>
                    <x-input-label for="from_contains" value="From contains" />
                    <x-text-input id="from_contains" name="from_contains" class="mt-1 block w-full" :value="old('from_contains', $fromContains)" />
                </div>
                <div>
                    <x-input-label for="body_contains" value="Body contains" />
                    <x-text-input id="body_contains" name="body_contains" class="mt-1 block w-full" :value="old('body_contains', $bodyContains)" />
                </div>
            </fieldset>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded text-brand" @checked(old('is_active', $rule->is_active))>
                <span>Active</span>
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="require_review" value="0">
                <input type="checkbox" name="require_review" value="1" class="rounded text-brand" @checked(old('require_review', $rule->require_review))>
                <span>Require admin/manager review before applying</span>
            </label>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.email.rules.index') }}" class="px-4 py-2 text-sm border">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
