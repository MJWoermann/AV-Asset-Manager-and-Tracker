@php
    $selectedFields = old('selected_fields', $template->selected_fields ?? []);
    $selectedRecipientIds = old('recipient_ids', $selectedRecipientIds ?? []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $template->exists ? 'Edit template' : 'New template' }}</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 space-y-4">
        <x-email-tabs />

        <form method="POST" action="{{ $template->exists ? route('admin.email.templates.update', $template) : route('admin.email.templates.store') }}" class="space-y-4 bg-white dark:bg-brand-slate border p-4">
            @csrf
            @if($template->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="event_key" value="Event" />
                    <select id="event_key" name="event_key" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                        @foreach($eventKeys as $value => $label)
                            <option value="{{ $value }}" @selected(old('event_key', $template->event_key) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('event_key')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $template->name)" required />
                </div>
            </div>

            <div>
                <x-input-label for="subject" value="Subject" />
                <x-text-input id="subject" name="subject" class="mt-1 block w-full" :value="old('subject', $template->subject)" required />
                <x-input-error :messages="$errors->get('subject')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="body" value="Body" />
                <textarea id="body" name="body" rows="10" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white font-mono text-sm" required>{{ old('body', $template->body) }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-1" />
                <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Use placeholders like <code>{{ '{{asset.name}}' }}</code> or <code>{{ '{{custom.mac_address_2}}' }}</code>.</p>
            </div>

            <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-3">
                <legend class="px-1 text-sm font-medium">Selected fields</legend>
                <div>
                    <p class="text-xs font-semibold mb-2">Core</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($catalog['core'] as $field)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="selected_fields[]" value="{{ $field['key'] }}" class="rounded text-brand" @checked(in_array($field['key'], $selectedFields, true))>
                                <span>{{ $field['label'] }} <code class="text-xs">{{ $field['key'] }}</code></span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @if(count($catalog['custom']) > 0)
                    <div>
                        <p class="text-xs font-semibold mb-2">Custom</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($catalog['custom'] as $field)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="selected_fields[]" value="{{ $field['key'] }}" class="rounded text-brand" @checked(in_array($field['key'], $selectedFields, true))>
                                    <span>{{ $field['label'] }} <code class="text-xs">{{ $field['key'] }}</code></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </fieldset>

            <fieldset class="border border-brand-silver dark:border-brand-charcoal p-3 space-y-3">
                <legend class="px-1 text-sm font-medium">Recipients</legend>
                @forelse($recipients as $recipient)
                    @php
                        $rid = (string) $recipient->id;
                        $required = old('required_fields.'.$recipient->id, $requiredByRecipient[$rid] ?? []);
                    @endphp
                    <div class="border border-brand-silver/60 dark:border-brand-charcoal p-3 space-y-2">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="recipient_ids[]" value="{{ $recipient->id }}" class="rounded text-brand" @checked(in_array($recipient->id, $selectedRecipientIds, false) || in_array((string) $recipient->id, array_map('strval', $selectedRecipientIds), true))>
                            <span>{{ $recipient->name }} ({{ $recipient->email }})</span>
                        </label>
                        <p class="text-xs text-brand-charcoal dark:text-brand-silver">Required fields for this recipient:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                            @foreach(array_merge($catalog['core'], $catalog['custom']) as $field)
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="required_fields[{{ $recipient->id }}][]" value="{{ $field['key'] }}" class="rounded text-brand" @checked(in_array($field['key'], $required, true))>
                                    <span>{{ $field['key'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm">Create recipients first.</p>
                @endforelse
            </fieldset>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded text-brand" @checked(old('is_active', $template->is_active ?? true))>
                <span>Active</span>
            </label>

            <div class="flex gap-2">
                <x-primary-button>Save</x-primary-button>
                <a href="{{ route('admin.email.templates.index') }}" class="px-4 py-2 text-sm border">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
