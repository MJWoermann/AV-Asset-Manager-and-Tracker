<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Import assets</h2></x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form method="POST" action="{{ route('import.upload') }}" enctype="multipart/form-data" class="space-y-4 bg-white dark:bg-black border p-4">
            @csrf
            <p class="text-sm text-gray-600 dark:text-brand-silver">
                Upload a CSV or Excel file. You will map columns (including item type and location) on the next step.
            </p>
            <div>
                <x-input-label for="file" value="CSV or Excel (max 25MB)" />
                <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls" required class="mt-1 block w-full text-sm">
                <x-input-error :messages="$errors->get('file')" class="mt-1" />
            </div>
            <x-primary-button>Upload &amp; map columns</x-primary-button>
        </form>
    </div>
</x-app-layout>
