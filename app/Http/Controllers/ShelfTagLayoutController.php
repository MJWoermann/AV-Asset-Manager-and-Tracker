<?php

namespace App\Http\Controllers;

use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Http\Requests\ImportShelfTagLbxRequest;
use App\Http\Requests\StoreShelfTagLayoutRequest;
use App\Http\Requests\UpdateShelfTagLayoutRequest;
use App\Models\File;
use App\Models\ShelfTagLayout;
use App\Services\FileAttachmentService;
use App\Services\ShelfTagLbxImporter;
use App\Support\ShelfTagFieldKeys;
use App\Support\ShelfTagPaperPresets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShelfTagLayoutController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('labels.index');
    }

    public function importForm(): View
    {
        return view('shelf-tags.layouts.import');
    }

    public function import(ImportShelfTagLbxRequest $request, ShelfTagLbxImporter $importer): RedirectResponse
    {
        try {
            $result = $importer->import($request->file('lbx'), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['lbx' => $exception->getMessage()]);
        }

        $status = 'Imported P-touch layout. Review field bindings and tweak in the designer.';
        $warnings = $result['warnings'];

        return redirect()
            ->route('labels.layouts.edit', $result['layout'])
            ->with('status', $status)
            ->with('import_warnings', $warnings);
    }

    public function create(): View
    {
        $layout = new ShelfTagLayout([
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => ShelfTagPaperPresets::defaultWidthMm(ShelfTagPaperPresets::DEFAULT),
            'height_mm' => ShelfTagPaperPresets::defaultHeightMm(ShelfTagPaperPresets::DEFAULT),
            'orientation' => 'portrait',
            'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_FIXED,
            'elements' => [],
            'editor_settings' => ShelfTagPaperPresets::defaultEditorSettings(),
        ]);

        return view('shelf-tags.layouts.form', $this->formData($layout));
    }

    public function store(StoreShelfTagLayoutRequest $request, FileAttachmentService $attachments): RedirectResponse
    {
        $data = $request->layoutAttributes();
        $data['created_by'] = $request->user()?->id;
        $this->applyLogo($request, $data, null, $attachments);

        $layout = ShelfTagLayout::query()->create($data);

        return redirect()
            ->route('labels.layouts.edit', $layout)
            ->with('status', 'Label layout created.');
    }

    public function edit(ShelfTagLayout $layout): View
    {
        return view('shelf-tags.layouts.form', $this->formData($layout));
    }

    public function update(UpdateShelfTagLayoutRequest $request, ShelfTagLayout $layout, FileAttachmentService $attachments): RedirectResponse
    {
        $data = $request->layoutAttributes();
        $this->applyLogo($request, $data, $layout, $attachments);

        $layout->update($data);

        return redirect()
            ->route('labels.layouts.edit', $layout)
            ->with('status', 'Label layout saved.');
    }

    public function duplicate(Request $request, ShelfTagLayout $layout): RedirectResponse
    {
        $copy = $layout->duplicateFor($request->user());

        return redirect()
            ->route('labels.layouts.edit', $copy)
            ->with('status', 'Label layout duplicated.');
    }

    public function destroy(ShelfTagLayout $layout): RedirectResponse
    {
        $layout->delete();

        return redirect()
            ->route('labels.index')
            ->with('status', 'Label layout deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(ShelfTagLayout $layout): array
    {
        return [
            'layout' => $layout,
            'presets' => ShelfTagPaperPresets::all(),
            'fieldOptions' => ShelfTagFieldKeys::optionsForDesigner(),
        ];
    }

    public function logoLibrary(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));

        $files = File::query()
            ->where('source_type', FileSourceType::Upload)
            ->whereIn('mime_type', ShelfTagLayout::LOGO_MIME_TYPES)
            ->when($term !== '', fn ($query) => $query->search($term))
            ->latest()
            ->limit(24)
            ->get();

        return response()->json([
            'data' => $files->map(fn (File $file): array => [
                'id' => $file->id,
                'title' => $file->title,
                'url' => route('labels.layouts.logo-file', $file),
            ])->values(),
        ]);
    }

    public function showLogoFile(Request $request, File $file): StreamedResponse
    {
        abort_unless(ShelfTagLayout::isUsableLogoFile($file), 404);

        $assigned = ShelfTagLayout::query()->where('logo_file_id', $file->id)->exists();
        abort_unless($assigned || $request->user()?->can('labels:write'), 404);

        $disk = Storage::disk((string) $file->disk);
        abort_unless($disk->exists((string) $file->path), 404);

        return $disk->response((string) $file->path, null, [
            'Content-Type' => (string) $file->mime_type,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyLogo(Request $request, array &$data, ?ShelfTagLayout $layout, FileAttachmentService $attachments): void
    {
        if ($request->hasFile('logo')) {
            $upload = $request->file('logo');
            if (! $upload instanceof UploadedFile) {
                return;
            }

            try {
                $stored = $attachments->storeUpload($upload, [
                    'title' => $this->logoTitle($upload),
                    'category' => FileCategory::Photo->value,
                ]);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'logo' => $exception->getMessage(),
                ]);
            }

            $layout?->releasePublicLogo();
            $data['logo_file_id'] = $stored->id;
            $data['logo_path'] = null;

            return;
        }

        if ($request->filled('logo_file_id')) {
            $layout?->releasePublicLogo();
            $data['logo_file_id'] = (int) $request->input('logo_file_id');
            $data['logo_path'] = null;

            return;
        }

        if ($request->boolean('remove_logo')) {
            $layout?->releasePublicLogo();
            $data['logo_file_id'] = null;
            $data['logo_path'] = null;
        }
    }

    protected function logoTitle(UploadedFile $upload): string
    {
        $name = trim(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME));

        return $name !== '' ? mb_substr($name, 0, 255) : 'Label logo';
    }
}
