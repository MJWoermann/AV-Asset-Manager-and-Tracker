<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Import assets</h2></x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form method="POST" action="{{ route('import.upload') }}" enctype="multipart/form-data" class="space-y-4 bg-white dark:bg-black border p-4">
            @csrf
            <div>
                <x-input-label for="item_type_id" value="Item type for imported rows" />
                <select name="item_type_id" id="item_type_id" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white" required>
                    @foreach($itemTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="file" value="CSV or Excel (max 25MB)" />
                <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls" required class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('file')" class="mt-1" />
            </div>
            <x-primary-button>Upload &amp; map columns</x-primary-button>
        </form>
    </div>
</x-app-layout>
