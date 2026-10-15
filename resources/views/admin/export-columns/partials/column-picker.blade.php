@props([
    'inputName',
    'selected',
    'availableColumns',
    'customFieldSets',
])

<div class="space-y-4 max-h-96 overflow-y-auto">
    <div class="space-y-1">
        @foreach($availableColumns as $key => $label)
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="{{ $inputName }}[]"
                    value="{{ $key }}"
                    class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                    @checked(in_array($key, old($inputName, $selected), true))
                    @if($key === 'name') disabled @endif
                >
                <span>{{ $label }}</span>
            </label>
            @if($key === 'name')
                <input type="hidden" name="{{ $inputName }}[]" value="name">
            @endif
        @endforeach
    </div>

    @foreach($customFieldSets as $fieldSet)
        <div class="border-t border-brand-charcoal/20 pt-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-charcoal dark:text-brand-silver mb-2">
                {{ $fieldSet['name'] }}
            </p>
            <div class="space-y-1">
                @foreach($fieldSet['fields'] as $field)
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="{{ $inputName }}[]"
                            value="{{ $field['key'] }}"
                            class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                            @checked(in_array($field['key'], old($inputName, $selected), true))
                        >
                        <span>{{ $field['label'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
