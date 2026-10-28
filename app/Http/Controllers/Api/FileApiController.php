<?php

namespace App\Http\Controllers\Api;

use App\Enums\FileCategory;
use App\Http\Controllers\Concerns\AuthorizesFileAttachments;
use App\Http\Controllers\Controller;
use App\Http\Requests\LinkFileRequest;
use App\Http\Requests\StoreBulkFileUploadRequest;
use App\Http\Requests\StoreFileUploadRequest;
use App\Http\Requests\StoreFileUrlRequest;
use App\Models\File;
use App\Models\FileLink;
use App\Models\User;
use App\Services\FileAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileApiController extends Controller
{
    use AuthorizesFileAttachments;

    public function __construct(protected FileAttachmentService $attachments) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManager();

        $query = File::query()->withCount('links')->with('uploader')->latest();

        if ($search = trim((string) $request->string('q'))) {
            $query->search($search);
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function show(File $file): JsonResponse
    {
        $this->authorizeManager();
        $file->load(['uploader', 'links.linkable']);

        return response()->json($file);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        /** @var User|null $user */
        $user = $request->user();
        $manager = $user?->can('files:manage') ?? false;

        $files = File::query()
            ->when(! $manager, function ($query): void {
                $query->whereHas('links');
            })
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($builder) use ($term): void {
                    $builder->where('title', 'like', '%'.$term.'%')
                        ->orWhere('original_filename', 'like', '%'.$term.'%');
                });
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

    public function upload(StoreFileUploadRequest $request): JsonResponse
    {
        $data = $request->validated();
        $linkable = $this->linkableFromRequest($data['linkable_type'] ?? null, $data['linkable_id'] ?? null);
        $this->authorizeMutate($linkable);

        $stored = $this->attachments->storeUpload($request->file('file'), [
            'title' => $data['title'],
            'category' => $data['category'],
            'note' => $data['note'] ?? null,
        ], $linkable);

        return response()->json($stored, 201);
    }

    public function bulkUpload(StoreBulkFileUploadRequest $request): JsonResponse
    {
        $data = $request->validated();
        $linkable = $this->linkableFromRequest($data['linkable_type'] ?? null, $data['linkable_id'] ?? null);
        $this->authorizeMutate($linkable);

        $items = [];
        foreach ($request->file('files', []) as $index => $upload) {
            $category = $data['categories'][$index];
            $items[] = [
                'file' => $upload,
                'title' => $data['titles'][$index],
                'category' => $category instanceof FileCategory ? $category->value : (string) $category,
                'note' => $data['notes'][$index] ?? null,
            ];
        }

        $stored = $this->attachments->storeBulkUploads($items, $linkable);

        return response()->json(['data' => $stored], 201);
    }

    public function storeUrl(StoreFileUrlRequest $request): JsonResponse
    {
        $data = $request->validated();
        $linkable = $this->linkableFromRequest($data['linkable_type'] ?? null, $data['linkable_id'] ?? null);
        $this->authorizeMutate($linkable);

        $file = $this->attachments->storeUrl([
            'title' => $data['title'],
            'category' => $data['category'],
            'external_url' => $data['external_url'],
            'note' => $data['note'] ?? null,
        ], $linkable);

        return response()->json($file, 201);
    }

    public function link(LinkFileRequest $request, File $file): JsonResponse
    {
        $data = $request->validated();
        $linkable = $this->attachments->resolveLinkable($data['linkable_type'], (int) $data['linkable_id']);
        $this->authorizeMutate($linkable);

        $link = $this->attachments->link($file, $linkable, $data['note'] ?? null);

        return response()->json($link, 201);
    }

    public function unlink(FileLink $fileLink): JsonResponse
    {
        $fileLink->loadMissing('linkable', 'file');
        $this->authorizeMutate($fileLink->linkable);
        $this->attachments->unlink($fileLink);

        return response()->json(['message' => 'File unlinked.']);
    }

    public function destroy(File $file): JsonResponse
    {
        $this->authorizeManager();
        $this->attachments->deleteFile($file);

        return response()->json(['message' => 'File deleted.']);
    }

    public function download(Request $request, File $file): StreamedResponse|JsonResponse
    {
        $this->authorizeView($file);

        if ($file->isUrl()) {
            return response()->json(['external_url' => $file->external_url]);
        }

        abort_unless($file->path && $file->disk, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        return $disk->download(
            $file->path,
            $file->original_filename ?: $file->title
        );
    }
}
