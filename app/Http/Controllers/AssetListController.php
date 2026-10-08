<?php

namespace App\Http\Controllers;

use App\Models\AssetList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetListController extends Controller
{
    public function index(): View
    {
        $lists = AssetList::withCount('items')->orderBy('type')->orderBy('name')->get();

        return view('lists.index', compact('lists'));
    }

    public function create(): View
    {
        return view('lists.form', ['list' => new AssetList(['type' => 'event'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:inventory,event'],
            'description' => ['nullable', 'string'],
        ]);

        $list = AssetList::create($data + ['created_by' => auth()->id()]);

        return redirect()->route('lists.show', $list)->with('status', 'List created.');
    }

    public function show(AssetList $list): View
    {
        $items = $list->items()
            ->with(['asset.itemType', 'asset.parent'])
            ->join('assets', 'assets.id', '=', 'asset_list_items.asset_id')
            ->orderByRaw('COALESCE(assets.parent_id, assets.id)')
            ->orderBy('assets.parent_id')
            ->orderBy('assets.name')
            ->select('asset_list_items.*')
            ->paginate(50);

        return view('lists.show', compact('list', 'items'));
    }

    public function edit(AssetList $list): View
    {
        return view('lists.form', compact('list'));
    }

    public function update(Request $request, AssetList $list): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:inventory,event'],
            'description' => ['nullable', 'string'],
        ]);
        $list->update($data);

        return redirect()->route('lists.show', $list)->with('status', 'List updated.');
    }

    public function destroy(AssetList $list): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'inventory_manager']), 403);
        $list->delete();

        return redirect()->route('lists.index')->with('status', 'List deleted.');
    }
}
