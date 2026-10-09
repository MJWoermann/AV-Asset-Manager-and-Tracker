<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\Location;
use App\Models\ScanSession;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        $active = ScanSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['eventList', 'inventoryList', 'location'])
            ->latest()
            ->first();

        $eventListItems = collect();

        if ($active) {
            $eventListItems = AssetListItem::query()
                ->where('asset_list_id', $active->event_list_id)
                ->with(['asset.itemType'])
                ->latest('id')
                ->get();
        }

        return view('scan.index', [
            'session' => $active,
            'eventListItems' => $eventListItems,
            'eventLists' => AssetList::where('type', 'event')->orderBy('name')->get(),
            'inventoryLists' => AssetList::where('type', 'inventory')->orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $this->validatedSessionLists($request);

        ScanSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->update(['status' => 'closed']);

        ScanSession::create($data + [
            'user_id' => $request->user()->id,
            'status' => 'active',
        ]);

        return redirect()->route('scan.index')->with('status', 'Scan session started.');
    }

    public function updateLists(Request $request, ScanSession $scan): RedirectResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        if (! $scan->isActive()) {
            return redirect()->route('scan.index')->withErrors([
                'event_list_id' => 'Scan session is closed.',
            ]);
        }

        $scan->update($this->validatedSessionLists($request));

        return redirect()->route('scan.index')->with('status', 'Scan lists updated.');
    }

    public function updateLocation(Request $request, ScanSession $scan): RedirectResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);
        $scan->update($data);

        return back()->with('status', 'Location updated for next scans.');
    }

    public function scan(Request $request, ScanSession $scan, ScanService $scanService): JsonResponse|RedirectResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $result = $scanService->scanCode($scan, $data['code'], $data['quantity'] ?? 1);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors());
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        $flash = $result['duplicate'] ? 'warning' : 'status';

        return back()->with($flash, $result['message'])->with('inventory_status', $result['inventory_status']);
    }

    public function close(Request $request, ScanSession $scan): RedirectResponse
    {
        abort_unless($scan->user_id === $request->user()->id, 403);
        $scan->update(['status' => 'closed']);

        return redirect()->route('lists.show', $scan->event_list_id)->with('status', 'Scan session closed.');
    }

    /**
     * @return array{event_list_id: int, inventory_list_id: int|null, location_id: int|null, asset_status: string|null}
     */
    protected function validatedSessionLists(Request $request): array
    {
        $creatingLocation = $request->input('location_id') === '__new__';

        $data = $request->validate([
            'event_list_id' => [
                'required',
                Rule::exists('asset_lists', 'id')->where('type', 'event'),
            ],
            'inventory_list_id' => [
                'nullable',
                Rule::exists('asset_lists', 'id')->where('type', 'inventory'),
            ],
            'location_id' => $creatingLocation
                ? ['required', 'in:__new__']
                : ['nullable', 'exists:locations,id'],
            'new_location.name' => [
                Rule::requiredIf($creatingLocation),
                'nullable',
                'string',
                'max:255',
            ],
            'new_location.type' => [
                Rule::requiredIf($creatingLocation),
                'nullable',
                Rule::enum(LocationType::class),
            ],
            'new_location.parent_id' => ['nullable', 'exists:locations,id'],
            'asset_status' => ['nullable', Rule::enum(AssetStatus::class)],
        ]);

        $data['inventory_list_id'] = $data['inventory_list_id'] ?? null;
        $data['asset_status'] = $data['asset_status'] ?? null;

        if ($creatingLocation) {
            $location = Location::create([
                'name' => $data['new_location']['name'],
                'type' => $data['new_location']['type'],
                'parent_id' => $data['new_location']['parent_id'] ?? null,
                'is_portable' => false,
            ]);

            $data['location_id'] = $location->id;
        } else {
            $data['location_id'] = $data['location_id'] ?? null;
        }

        unset($data['new_location']);

        return $data;
    }
}
