<?php

namespace App\Http\Controllers;

use App\Enums\FileCategory;
use App\Http\Requests\LinkFileRequest;
use App\Http\Requests\StoreFileUploadRequest;
use App\Http\Requests\StoreFileUrlRequest;
use App\Models\File;
use App\Models\FileLink;
use App\Models\User;
use App\Services\FileAttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(private FileAttachmentService $attachments) {}

    public function index(Request $request): View
    {
        $this->authorizeManager();

        $query = File::query()
            ->withCount('links')
            ->with('uploader')
            ->latest();

        if ($search = trim((string) $request->string('q'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('original_filename', 'like', '%'.$search.'%')
                    ->orWhere('external_url', 'like', '%'.$search.'%');
            });
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

        $files = File::query()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('title', 'like', '%'.$term.'%')
                    ->orWhere('original_filename', 'like', '%'.$term.'%');
            })
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

    public function download(File $file): StreamedResponse|RedirectResponse
    {
        if ($file->isUrl()) {
            $scheme = strtolower((string) parse_url((string) $file->external_url, PHP_URL_SCHEME));
            abort_unless(in_array($scheme, ['http', 'https'], true), 404);

            return redirect()->away($file->external_url);
        }

        abort_unless($file->path && $file->disk, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        return $disk->download(
            $file->path,
            $file->original_filename ?: $file->title
        );
    }

    public function preview(File $file): StreamedResponse|RedirectResponse
    {
        if ($file->isUrl()) {
            $scheme = strtolower((string) parse_url((string) $file->external_url, PHP_URL_SCHEME));
            abort_unless(in_array($scheme, ['http', 'https'], true), 404);

            return redirect()->away($file->external_url);
        }

        abort_unless($file->isPreviewable(), 415);
        abort_unless($file->path && $file->disk, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        $filename = $file->original_filename ?: $file->title;
        $disposition = 'inline; filename="'.addslashes($filename).'"';

        return $disk->response($file->path, $filename, [
            'Content-Type' => (string) $file->mime_type,
            'Content-Disposition' => $disposition,
        ]);
    }

    public function thumbnail(File $file): StreamedResponse
    {
        abort_unless($file->thumbnail_path && $file->disk, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->thumbnail_path), 404);

        return $disk->response($file->thumbnail_path);
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

    protected function authorizeManager(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user?->hasAnyRole(['admin', 'inventory_manager']), 403);
    }
}
