@props([
    'availableColumns',
    'selectedColumns',
])

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        class="text-sm px-3 py-2 border border-brand-charcoal dark:border-brand-silver"
        @click="open = ! open"
        :aria-expanded="open.toString()"
    >
        Columns
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-40 mt-2 end-0 w-64 bg-white dark:bg-black border border-brand-silver dark:border-brand-charcoal p-3 shadow-lg"
        style="display: none;"
    >
        <form method="POST" action="{{ route('preferences.asset-columns') }}" class="space-y-2">
            @csrf
            @method('PATCH')
            <p class="text-xs text-brand-charcoal dark:text-brand-silver mb-2">Choose columns for asset tables. Saved to your profile.</p>
            <div class="max-h-64 overflow-y-auto space-y-1">
                @foreach($availableColumns as $key => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="columns[]"
                            value="{{ $key }}"
                            class="rounded border-brand-charcoal/40 text-brand focus:ring-brand"
                            @checked(in_array($key, $selectedColumns, true))
                            @if($key === 'name') disabled @endif
                        >
                        <span>{{ $label }}</span>
                    </label>
                    @if($key === 'name')
                        <input type="hidden" name="columns[]" value="name">
                    @endif
                @endforeach
            </div>
            <button type="submit" class="w-full mt-2 px-3 py-2 bg-black text-white dark:bg-brand text-sm">
                Save columns
            </button>
        </form>
    </div>
</div>
