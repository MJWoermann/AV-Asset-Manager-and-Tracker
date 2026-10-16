<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpareGroupRequest;
use App\Http\Requests\UpdateSpareGroupRequest;
use App\Models\Asset;
use App\Models\Location;
use App\Models\SpareGroup;
use App\Models\SparePart;
use App\Services\SpareGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SpareGroupController extends Controller
{
    public function index(): View
    {
        $groups = SpareGroup::query()
            ->withCount('items')
            ->with(['location', 'linkedSystem'])
            ->orderBy('name')
            ->paginate(25);

        return view('spares.groups.index', compact('groups'));
    }

    public function create(): View
    {
        return view('spares.groups.form', $this->formData(new SpareGroup));
    }

    public function store(StoreSpareGroupRequest $request, SpareGroupService $service): RedirectResponse
    {
        $data = $request->validated();
        $group = $service->create($data, $data['items'] ?? []);

        return redirect()->route('spares.groups.show', $group)->with('status', 'Spare group created.');
    }

    public function show(SpareGroup $group): View
    {
        $group->load(['items.sparePart.manufacturer', 'items.sparePart.manufacturerModel', 'location', 'linkedSystem']);

        return view('spares.groups.show', compact('group'));
    }

    public function edit(SpareGroup $group): View
    {
        $group->load('items');

        return view('spares.groups.form', $this->formData($group));
    }

    public function update(UpdateSpareGroupRequest $request, SpareGroup $group, SpareGroupService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->update($group, $data, $data['items'] ?? []);

        return redirect()->route('spares.groups.show', $group)->with('status', 'Spare group updated.');
    }

    public function destroy(SpareGroup $group): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'inventory_manager']), 403);
        $group->delete();

        return redirect()->route('spares.groups.index')->with('status', 'Spare group deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(SpareGroup $group): array
    {
        return [
            'group' => $group,
            'locations' => Location::query()->orderBy('name')->get(),
            'assets' => Asset::query()->orderBy('name')->limit(200)->get(['id', 'name']),
            'spareOptions' => SparePart::query()->orderBy('code')->get(['id', 'code', 'component']),
        ];
    }
}
