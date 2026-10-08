<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Map columns — {{ $itemType->name }}</h2></x-slot>
    <div class="max-w-2xl mx-auto px-4">
        <form method="POST" action="{{ route('import.process') }}" class="space-y-4 bg-white dark:bg-black border p-4">
            @csrf
            @foreach($columns as $column)
                <div class="grid grid-cols-2 gap-3 items-center text-sm">
                    <div class="font-mono">{{ $column }}</div>
                    <select name="mapping[{{ $column }}]" class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                        <option value="">Ignore</option>
                        @foreach($fields as $field => $label)
                            <option value="{{ $field }}" @selected(str_contains(strtolower($column), str_replace('_', ' ', $field)) || str_contains(strtolower($column), strtolower($label)))>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="dry_run" value="1" class="rounded text-brand" checked>
                Dry run (no writes)
            </label>
            <x-primary-button>Process import</x-primary-button>
        </form>
    </div>
</x-app-layout>
