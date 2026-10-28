<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $role->exists ? 'Edit role' : 'New role' }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4">
        <form
            method="POST"
            action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
            class="space-y-6 bg-white dark:bg-brand-slate border border-brand-silver dark:border-brand-charcoal p-4"
        >
            @csrf
            @if($role->exists) @method('PUT') @endif

            <div>
                <x-input-label for="name" value="Name" required />
                <x-text-input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    :value="old('name', $role->name)"
                    :readonly="$systemRole"
                    required
                />
                @if($systemRole)
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">System role names stay fixed.</p>
                @endif
                @if($locked)
                    <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Administrators have every operational permission. Optional previews stay on each administrator's profile.</p>
                @endif
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            @foreach($groups as $group => $permissions)
                <fieldset
                    class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-4"
                    x-data="{
                        toggle(on) {
                            this.$root.querySelectorAll('input[type=checkbox][data-permission]').forEach((el) => {
                                if (!el.disabled) {
                                    el.checked = on
                                }
                            })
                        }
                    }"
                >
                    <div class="flex items-center justify-between gap-3">
                        <legend class="text-sm font-semibold">{{ $group }}</legend>
                        @unless($locked)
                            <label class="inline-flex items-center gap-2 text-xs text-brand-charcoal dark:text-brand-silver">
                                <input type="checkbox" class="rounded text-brand" @change="toggle($event.target.checked)">
                                Select all
                            </label>
                        @endunless
                    </div>
                    @foreach($permissions as $permission => $label)
                        <label class="flex items-start gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission }}"
                                data-permission
                                class="mt-0.5 rounded text-brand"
                                @checked($locked || in_array($permission, $selected, true))
                                @disabled($locked)
                            >
                            <span>
                                <span class="font-medium">{{ $label }}</span>
                                <span class="block text-xs text-brand-charcoal dark:text-brand-silver font-mono">{{ $permission }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            @endforeach

            @if($showFeatures)
                <fieldset
                    class="space-y-2 border border-brand-silver dark:border-brand-charcoal p-4"
                    x-data="{
                        toggle(on) {
                            this.$root.querySelectorAll('input[type=checkbox][data-permission]').forEach((el) => {
                                el.checked = on
                            })
                        }
                    }"
                >
                    <div class="flex items-center justify-between gap-3">
                        <legend class="text-sm font-semibold">Developer features</legend>
                        <label class="inline-flex items-center gap-2 text-xs text-brand-charcoal dark:text-brand-silver">
                            <input type="checkbox" class="rounded text-brand" @change="toggle($event.target.checked)">
                            Select all
                        </label>
                    </div>
                    <p class="text-xs text-brand-charcoal dark:text-brand-silver">
                        A feature also needs the matching operational permission before its screens open. External repairers stay on their assigned repair work.
                    </p>
                    @foreach($features as $permission => $label)
                        <label class="flex items-start gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission }}"
                                data-permission
                                class="mt-0.5 rounded text-brand"
                                @checked(in_array($permission, $selected, true))
                            >
                            <span>
                                <span class="font-medium">{{ $label }}</span>
                                <span class="block text-xs text-brand-charcoal dark:text-brand-silver font-mono">{{ $permission }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            @endif

            <x-input-error :messages="$errors->get('permissions')" class="mt-1" />
            <x-input-error :messages="$errors->get('permissions.*')" class="mt-1" />

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center px-4 py-2 text-sm border border-brand-silver dark:border-brand-charcoal">Cancel</a>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
