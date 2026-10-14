<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShelfTagLayoutRequest;
use App\Http\Requests\UpdateShelfTagLayoutRequest;
use App\Models\ShelfTagLayout;
use App\Support\ShelfTagFieldKeys;
use App\Support\ShelfTagPaperPresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShelfTagLayoutController extends Controller
{
    public function index(): View
    {
        $layouts = ShelfTagLayout::query()
            ->orderBy('name')
            ->get();

        return view('shelf-tags.layouts.index', compact('layouts'));
    }

    public function create(): View
    {
        $layout = new ShelfTagLayout([
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => ShelfTagPaperPresets::defaultWidthMm(ShelfTagPaperPresets::DEFAULT),
            'height_mm' => ShelfTagPaperPresets::defaultHeightMm(ShelfTagPaperPresets::DEFAULT),
            'orientation' => 'portrait',
            'elements' => [],
        ]);

        return view('shelf-tags.layouts.form', $this->formData($layout));
    }

    public function store(StoreShelfTagLayoutRequest $request): RedirectResponse
    {
        $data = $request->layoutAttributes();
        $data['created_by'] = $request->user()?->id;
        $data['logo_path'] = $this->storeLogo($request);

        $layout = ShelfTagLayout::query()->create($data);

        return redirect()
            ->route('shelf-tags.layouts.edit', $layout)
            ->with('status', 'Shelf tag layout created.');
    }

    public function edit(ShelfTagLayout $layout): View
    {
        return view('shelf-tags.layouts.form', $this->formData($layout));
    }

    public function update(UpdateShelfTagLayoutRequest $request, ShelfTagLayout $layout): RedirectResponse
    {
        $data = $request->layoutAttributes();

        if ($request->boolean('remove_logo') && $layout->logo_path) {
            Storage::disk('public')->delete($layout->logo_path);
            $data['logo_path'] = null;
        }

        $newLogo = $this->storeLogo($request);
        if ($newLogo !== null) {
            if ($layout->logo_path) {
                Storage::disk('public')->delete($layout->logo_path);
            }
            $data['logo_path'] = $newLogo;
        }

        $layout->update($data);

        return redirect()
            ->route('shelf-tags.layouts.edit', $layout)
            ->with('status', 'Shelf tag layout saved.');
    }

    public function destroy(ShelfTagLayout $layout): RedirectResponse
    {
        $layout->delete();

        return redirect()
            ->route('shelf-tags.layouts.index')
            ->with('status', 'Shelf tag layout deleted.');
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

    protected function storeLogo(Request $request): ?string
    {
        if (! $request->hasFile('logo')) {
            return null;
        }

        return $request->file('logo')->store('shelf-tag-logos', 'public');
    }
}
