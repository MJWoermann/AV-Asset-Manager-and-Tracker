<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $department->exists ? 'Edit department' : 'New department' }}</h2>
    </x-slot>
    <div class="max-w-xl mx-auto px-4">
        <form
            method="POST"
            action="{{ $department->exists ? route('admin.departments.update', $department) : route('admin.departments.store') }}"
            class="space-y-4 bg-white dark:bg-brand-slate border p-4"
        >
            @csrf
            @if($department->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" required />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $department->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded text-brand" @checked(old('is_active', $department->is_active ?? true))>
                Active
            </label>
            <x-input-error :messages="$errors->get('is_active')" class="mt-1" />

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.departments.index') }}" class="inline-flex items-center px-4 py-2 text-sm border border-brand-silver dark:border-brand-charcoal">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
