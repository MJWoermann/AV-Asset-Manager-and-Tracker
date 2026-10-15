<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Inbound email review</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 space-y-4">
        @if (session('status'))
            <p class="text-sm text-brand">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('admin.email.inbound.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 block rounded border-gray-300 dark:bg-black dark:border-brand-charcoal dark:text-white">
                    <option value="all" @selected($status === 'all')>All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>Filter</x-primary-button>
        </form>

        @forelse($actions as $action)
            <div class="bg-white dark:bg-brand-slate border p-4 space-y-2">
                <div class="flex flex-wrap justify-between gap-2">
                    <div>
                        <div class="font-medium">{{ $action->subject ?: '(no subject)' }}</div>
                        <div class="text-sm text-brand-charcoal dark:text-brand-silver">
                            From {{ $action->from_address ?: 'unknown' }}
                            · {{ $action->status?->label() ?? $action->status }}
                            @if($action->asset)
                                · <a href="{{ route('assets.show', [$action->asset, 'tab' => 'maintenance']) }}" class="text-brand">{{ $action->asset->name }}</a>
                            @else
                                · No asset matched
                            @endif
                            @if($action->rule)
                                · Rule: {{ $action->rule->name }}
                            @endif
                        </div>
                    </div>
                    @if($action->status?->value === 'pending')
                        <div class="flex gap-2 text-sm">
                            <form method="POST" action="{{ route('admin.email.inbound.apply', $action) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-brand text-white">Apply</button>
                            </form>
                            <form method="POST" action="{{ route('admin.email.inbound.reject', $action) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 border">Reject</button>
                            </form>
                        </div>
                    @endif
                </div>
                @if($action->snippet)
                    <p class="text-sm whitespace-pre-wrap">{{ $action->snippet }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-brand-charcoal dark:text-brand-silver">No inbound actions for this filter.</p>
        @endforelse

        {{ $actions->links() }}
    </div>
</x-app-layout>
