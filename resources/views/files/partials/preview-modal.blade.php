{{-- Expects Alpine: previewOpen, previewUrl, previewDownloadUrl, previewKind, previewTitle, closePreview() --}}
<div
    x-show="previewOpen"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="closePreview()"
>
    <div class="fixed inset-0 bg-gray-500/75" @click="closePreview()"></div>
    <div class="relative mx-auto w-full max-w-5xl bg-white dark:bg-brand-slate dark:text-white shadow-xl sm:rounded-lg overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-brand-silver dark:border-brand-charcoal px-4 py-3">
            <h3 class="font-medium truncate" x-text="previewTitle"></h3>
            <div class="flex items-center gap-3 shrink-0">
                <a x-show="previewDownloadUrl" :href="previewDownloadUrl" class="text-sm text-brand">Download</a>
                <button type="button" class="text-sm text-brand-charcoal dark:text-brand-silver" @click="closePreview()">Close</button>
            </div>
        </div>
        <div class="p-4">
            <img
                x-show="previewKind === 'image'"
                :src="previewUrl"
                :alt="previewTitle"
                class="max-h-[80vh] max-w-full mx-auto"
            >
            <iframe
                x-show="previewKind === 'pdf'"
                :src="previewUrl"
                class="w-full h-[70vh] sm:h-[80vh] border-0"
                :title="previewTitle"
            ></iframe>
        </div>
    </div>
</div>
