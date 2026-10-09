<?php

namespace App\Http\Controllers;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::tree();

        return view('locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('locations.form', [
            'location' => new Location(['type' => LocationType::Site]),
            'parents' => Location::orderBy('name')->get(),
            'types' => LocationType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Location::create($this->validated($request));

        return redirect()->route('locations.index')->with('status', 'Location created.');
    }

    public function edit(Location $location): View
    {
        return view('locations.form', [
            'location' => $location,
            'parents' => Location::where('id', '!=', $location->id)->orderBy('name')->get(),
            'types' => LocationType::cases(),
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $location->update($this->validated($request));

        return redirect()->route('locations.index')->with('status', 'Location updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $location->delete();

        return redirect()->route('locations.index')->with('status', 'Location deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:site,level,room,rack'],
            'parent_id' => ['nullable', 'exists:locations,id'],
            'is_portable' => ['sometimes', 'boolean'],
        ]) + ['is_portable' => $request->boolean('is_portable')];
    }
}
