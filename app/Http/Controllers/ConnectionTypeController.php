<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesConnectionTypeSlug;
use App\Http\Requests\StoreConnectionTypeRequest;
use App\Http\Requests\UpdateConnectionTypeRequest;
use App\Models\ConnectionType;
use App\Support\ConnectionTypeColors;
use App\Support\ConnectionTypeIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConnectionTypeController extends Controller
{
    use GeneratesConnectionTypeSlug;

    public function index(): View
    {
        $connectionTypes = ConnectionType::query()
            ->withCount(['connections', 'buses'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('connection-types.index', compact('connectionTypes'));
    }

    public function create(): View
    {
        $connectionType = new ConnectionType([
            'color' => '#00adb7',
            'icon' => 'generic',
            'sort_order' => 100,
        ]);

        return view('connection-types.form', [
            'connectionType' => $connectionType,
            'icons' => ConnectionTypeIcons::keys(),
            'colors' => ConnectionTypeColors::optionsFor($connectionType->color),
        ]);
    }

    public function store(StoreConnectionTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $slug = $this->uniqueSlug($data['name']);

        ConnectionType::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'color' => strtolower($data['color']),
            'icon' => $data['icon'],
            'allows_protocol' => $request->boolean('allows_protocol'),
            'allows_bus' => $request->boolean('allows_bus'),
            'sort_order' => (int) ($data['sort_order'] ?? 100),
        ]);

        return redirect()->route('connection-types.index')->with('status', 'Connection type created.');
    }

    public function edit(ConnectionType $connectionType): View
    {
        $connectionType->loadCount(['connections', 'buses']);

        return view('connection-types.form', [
            'connectionType' => $connectionType,
            'icons' => ConnectionTypeIcons::keys(),
            'colors' => ConnectionTypeColors::optionsFor($connectionType->color),
        ]);
    }

    public function update(UpdateConnectionTypeRequest $request, ConnectionType $connectionType): RedirectResponse
    {
        $data = $request->validated();

        $connectionType->update([
            'name' => $data['name'],
            'color' => strtolower($data['color']),
            'icon' => $data['icon'],
            'allows_protocol' => $request->boolean('allows_protocol'),
            'allows_bus' => $request->boolean('allows_bus'),
            'sort_order' => (int) ($data['sort_order'] ?? $connectionType->sort_order),
        ]);

        return redirect()->route('connection-types.index')->with('status', 'Connection type updated.');
    }

    public function destroy(ConnectionType $connectionType): RedirectResponse
    {
        if ($connectionType->connections()->exists() || $connectionType->buses()->exists()) {
            return back()->withErrors([
                'connection_type' => 'Cannot delete a connection type that is in use. Remove its connections and buses first.',
            ]);
        }

        $connectionType->delete();

        return redirect()->route('connection-types.index')->with('status', 'Connection type deleted.');
    }
}
