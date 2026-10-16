<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $recipient->exists ? 'Edit recipient' : 'New recipient' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 space-y-4">
        <x-email-tabs />

        <form method="POST" action="{{ $recipient->exists ? route('admin.email.recipients.update', $recipient) : route('admin.email.recipients.store') }}" class="space-y-4 bg-white dark:bg-brand-slate border p-4">
            @csrf
            @if($recipient->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $recipient->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $recipient->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="role_tag" value="Role tag" />
                <x-text-input id="role_tag" name="role_tag" class="mt-1 block w-full" :value="old('role_tag', $recipient->role_tag)" placeholder="it_helpdesk" />
            </div>
            <div>
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">{{ old('notes', $recipient->notes) }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded text-brand" @checked(old('is_active', $recipient->is_active))>
                <span>Active</span>
            </label>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.email.recipients.index') }}" class="px-4 py-2 text-sm border">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
