<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl">Import P-touch layout</h2>
            <a href="{{ route('labels.index') }}" class="text-sm text-brand">Back to labels</a>
        </div>
    </x-slot>

    <div class="max-w-xl mx-auto px-4 space-y-4">
        <p class="text-sm text-brand-charcoal dark:text-brand-silver">
            Upload a Brother P-touch Editor <code class="text-xs">.lbx</code> file. Geometry and static text/QR/logo are rebuilt into a label layout; bind asset fields in the designer after import.
        </p>

        @if ($errors->any())
            <div class="border border-red-500/40 bg-red-50 dark:bg-red-950/30 p-3 text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('labels.layouts.import.store') }}" enctype="multipart/form-data" class="space-y-4 bg-white dark:bg-brand-slate border p-4">
            @csrf
            <div>
                <x-input-label for="lbx" value="P-touch .lbx file" />
                <input
                    id="lbx"
                    name="lbx"
                    type="file"
                    accept=".lbx,.zip,application/zip"
                    required
                    class="mt-1 block w-full text-sm"
                >
                <p class="mt-1 text-xs text-brand-charcoal dark:text-brand-silver">Max 10 MB. ZIP-based Brother label archives only.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="px-3 py-2 bg-brand text-white text-sm">Import layout</button>
                <a href="{{ route('labels.index') }}" class="px-3 py-2 border text-sm inline-flex items-center">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
