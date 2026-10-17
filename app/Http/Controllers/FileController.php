<?php

namespace App\Http\Controllers;

use App\Enums\FileCategory;
use App\Http\Requests\LinkFileRequest;
use App\Http\Requests\StoreBulkFileUploadRequest;
use App\Http\Requests\StoreFileUploadRequest;
use App\Http\Requests\StoreFileUrlRequest;
use App\Models\File;
use App\Models\FileLink;
use App\Models\User;
use App\Services\FileAttachmentService;
use App\Support\TrustedExternalDomains;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(protected FileAttachmentService $attachments) {}

    public function index(Request $request): View
    {
        $this->authorizeManager();

        $query = File::query()
            ->withCount('links')
            ->with('uploader')
            ->latest();

        if ($search = trim((string) $request->string('q'))) {
            $query->search($search);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        return view('files.index', [
            'files' => $query->paginate(25)->withQueryString(),
            'categories' => FileCategory::options(),
        ]);
    }

    public function show(File $file): View
    {
        $this->authorizeManager();

        $file->load(['uploader', 'links.linkable']);

        return view('files.show', [
            'file' => $file,
            'links' => $file->links,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        /** @var User|null $user */
        $user = $request->user();
        $manager = $user?->hasAnyRole(['admin', 'inventory_manager']) ?? false;

        $files = File::query()
            ->when(! $manager, function ($query): void {
                $query->whereHas('links');
            })
            ->when($term !== '', fn ($query) => $query->search($term))
            ->orderBy('title')
            ->limit(20)
            ->get(['id', 'title', 'category', 'source_type', 'mime_type']);

        return response()->json([
            'data' => $files->map(fn (File $file): array => [
                'id' => $file->id,
                'title' => $file->title,
                'category' => $file->category->label(),
                'source_type' => $file->source_type->value,
            ]),
        ]);
    }

    public function storeUpload(StoreFileUploadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $linkable = $this->linkableFromRequest($data['linkable_type'] ?? null, $data['linkable_id'] ?? null);
        $this->authorizeMutate($linkable);

        try {
            $this->attachments->storeUpload($request->file('file'), [
                'title' => $data['title'],
                'category' => $data['category'],
                'note' => $data['note'] ?? null,
            ], $linkable);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return back()->with('status', 'File uploaded.');
    }

    public function storeBulkUpload(StoreBulkFileUploadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $linkable = $this->linkableFromRequest($data['linkable_type'] ?? null, $data['linkable_id'] ?? null);
        $this->authorizeMutate($linkable);

        $uploads = $request->file('files', []);
        $items = [];

        foreach ($uploads as $index => $upload) {
            $category = $data['categories'][$index];

            $items[] = [
                'file' => $upload,
                'title' => $data['titles'][$index],
                'category' => $category instanceof FileCategory ? $category->value : (string) $category,
                'note' => $data['notes'][$index] ?? null,
            ];
        }

        try {
            $stored = $this->attachments->storeBulkUploads($items, $linkable);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['files' => $exception->getMessage()]);
        }

        $count = count($stored);

        return back()->with('status', $count === 1 ? '1 file uploaded.' : "{$count} files uploaded.");
    }

    public function storeUrl(StoreFileUrlRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $linkable = $this->linkableFromRequest($data['linkable_type'] ?? null, $data['linkable_id'] ?? null);
        $this->authorizeMutate($linkable);

        $this->attachments->storeUrl([
            'title' => $data['title'],
            'category' => $data['category'],
            'external_url' => $data['external_url'],
            'note' => $data['note'] ?? null,
        ], $linkable);

        return back()->with('status', 'URL attached.');
    }

    public function link(LinkFileRequest $request, File $file): RedirectResponse
    {
        $data = $request->validated();
        $linkable = $this->attachments->resolveLinkable($data['linkable_type'], (int) $data['linkable_id']);
        $this->authorizeMutate($linkable);

        $this->attachments->link($file, $linkable, $data['note'] ?? null);

        return back()->with('status', 'File linked.');
    }

    public function unlink(FileLink $fileLink): RedirectResponse
    {
        $fileLink->loadMissing('linkable', 'file');
        $this->authorizeMutate($fileLink->linkable);

        $this->attachments->unlink($fileLink);

        return back()->with('status', 'File unlinked.');
    }

    public function destroy(File $file): RedirectResponse
    {
        $this->authorizeManager();
        $this->attachments->deleteFile($file);

        return redirect()->route('files.index')->with('status', 'File deleted.');
    }

    public function download(Request $request, File $file): StreamedResponse|RedirectResponse|View
    {
        $this->authorizeView($file);

        if ($file->isUrl()) {
            return $this->redirectExternalUrl($request, $file);
        }

        abort_unless($file->path && $file->disk, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        return $disk->download(
            $file->path,
            $file->original_filename ?: $file->title
        );
    }

    public function preview(Request $request, File $file): StreamedResponse|RedirectResponse|View
    {
        $this->authorizeView($file);

        if ($file->isUrl()) {
            return $this->redirectExternalUrl($request, $file);
        }

        abort_unless($file->isPreviewable(), 415);
        abort_unless($file->path && $file->disk, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        $filename = (string) ($file->original_filename ?: $file->title ?: 'file');
        $filename = str_replace(['/', '\\'], '_', $filename);
        $fallback = preg_replace('/[^\x20-\x7e]/', '_', $filename) ?: 'file';
        $fallback = str_replace('%', '_', $fallback);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $filename,
            $fallback
        );

        return $disk->response($file->path, $filename, [
            'Content-Type' => (string) $file->mime_type,
            'Content-Disposition' => $disposition,
        ]);
    }

    public function thumbnail(File $file): StreamedResponse
    {
        $this->authorizeView($file);

        abort_unless($file->thumbnail_path && $file->disk, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->thumbnail_path), 404);

        return $disk->response($file->thumbnail_path);
    }

    public function continueExternal(Request $request, File $file): RedirectResponse
    {
        $this->authorizeView($file);
        abort_unless($file->isUrl(), 404);

        $url = (string) $file->external_url;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        abort_unless(in_array($scheme, ['http', 'https'], true), 404);

        $host = TrustedExternalDomains::hostFromUrl($url);
        abort_unless($host !== null, 404);

        $request->validate([
            'trust' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('trust')) {
            $user = $request->user();
            $user->preferences = TrustedExternalDomains::mergeAdd($user->preferences, $url);
            $user->save();
        }

        return redirect()->away($url);
    }

    protected function redirectExternalUrl(Request $request, File $file): RedirectResponse|View
    {
        $url = (string) $file->external_url;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        abort_unless(in_array($scheme, ['http', 'https'], true), 404);

        $host = TrustedExternalDomains::hostFromUrl($url);
        abort_unless($host !== null, 404);

        /** @var User $user */
        $user = $request->user();

        if (TrustedExternalDomains::isTrusted($user->preferences, $url)) {
            return redirect()->away($url);
        }

        return view('files.external-confirm', [
            'file' => $file,
            'url' => $url,
            'host' => $host,
        ]);
    }

    protected function linkableFromRequest(?string $type, mixed $id): ?Model
    {
        if ($type === null || $id === null || $id === '') {
            return null;
        }

        return $this->attachments->resolveLinkable($type, (int) $id);
    }

    protected function authorizeMutate(?Model $linkable): void
    {
        if ($linkable === null) {
            $this->authorizeManager();

            return;
        }

        $type = $this->attachments->linkableTypeKey($linkable);

        if (in_array($type, ['location', 'item_type'], true)) {
            $this->authorizeManager();
        }
    }

    protected function authorizeView(File $file): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user?->hasAnyRole(['admin', 'inventory_manager'])) {
            return;
        }

        abort_unless($user !== null && $file->links()->exists(), 403);
    }

    protected function authorizeManager(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user?->hasAnyRole(['admin', 'inventory_manager']), 403);
    }
}
