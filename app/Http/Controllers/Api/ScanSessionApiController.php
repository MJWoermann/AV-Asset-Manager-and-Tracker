<?php

namespace App\Http\Controllers\Api;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Http\Controllers\Api\Concerns\AuthorizesScanSession;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\Location;
use App\Models\ScanSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScanSessionApiController extends Controller
{
    use AuthorizesScanSession;

    public function active(Request $request): JsonResponse
    {
        $session = ScanSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['eventList', 'inventoryList', 'location'])
            ->latest()
            ->first();

        return response()->json(['data' => $session]);
    }

    public function show(Request $request, ScanSession $scanSession): JsonResponse
    {
        $this->authorizeScanSession($request, $scanSession);
        $scanSession->load(['eventList', 'inventoryList', 'location']);

        return response()->json($scanSession);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedSessionLists($request);

        ScanSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->update(['status' => 'closed']);

        $session = ScanSession::create($data + [
            'department_id' => $data['department_id'],
            'user_id' => $request->user()->id,
            'status' => 'active',
        ]);

        return response()->json($session->load(['eventList', 'inventoryList', 'location']), 201);
    }

    public function updateLists(Request $request, ScanSession $scanSession): JsonResponse
    {
        $this->authorizeScanSession($request, $scanSession);

        if (! $scanSession->isActive()) {
            throw ValidationException::withMessages([
                'event_list_id' => 'Scan session is closed.',
            ]);
        }

        $scanSession->update($this->validatedSessionLists($request));

        return response()->json($scanSession->fresh(['eventList', 'inventoryList', 'location']));
    }

    public function updateLocation(Request $request, ScanSession $scanSession): JsonResponse
    {
        $this->authorizeScanSession($request, $scanSession);

        $data = $request->validate([
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);
        $scanSession->update($data);

        return response()->json($scanSession->fresh(['location']));
    }

    public function close(Request $request, ScanSession $scanSession): JsonResponse
    {
        $this->authorizeScanSession($request, $scanSession);
        $scanSession->update(['status' => 'closed']);

        return response()->json($scanSession->fresh());
    }

    /**
     * @return array{event_list_id: int|null, inventory_list_id: int|null, location_id: int|null, asset_status: string|null, parent_asset_id: int|null, replace_existing_parent: bool, department_id: int}
     */
    protected function validatedSessionLists(Request $request): array
    {
        $creatingLocation = $request->input('location_id') === '__new__';

        $data = $request->validate([
            'event_list_id' => [
                'nullable',
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
            'new_location.parent_id' => [
                'nullable',
                Rule::exists('locations', 'id')->whereNot('type', LocationType::Rack->value),
            ],
            'asset_status' => ['nullable', Rule::enum(AssetStatus::class)],
            'parent_asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'replace_existing_parent' => ['sometimes', 'boolean'],
        ], [
            'new_location.parent_id.exists' => 'Racks cannot be a parent for another location.',
        ]);

        $data['event_list_id'] = $data['event_list_id'] ?? null;
        $eventList = null;
        if ($data['event_list_id'] !== null) {
            $eventList = AssetList::query()->find($data['event_list_id']);
            if ($eventList === null) {
                throw ValidationException::withMessages([
                    'event_list_id' => 'Selected event list is not available.',
                ]);
            }
        }

        $inventoryList = null;
        if (! empty($data['inventory_list_id'])) {
            $inventoryList = AssetList::query()->find($data['inventory_list_id']);
            if ($inventoryList === null) {
                throw ValidationException::withMessages([
                    'inventory_list_id' => 'Selected inventory list is not available.',
                ]);
            }

            if ($eventList !== null && (int) $inventoryList->department_id !== (int) $eventList->department_id) {
                throw ValidationException::withMessages([
                    'inventory_list_id' => 'Event and inventory lists must belong to the same department.',
                ]);
            }
        }

        $data['inventory_list_id'] = $data['inventory_list_id'] ?? null;
        $data['asset_status'] = isset($data['asset_status']) && $data['asset_status'] instanceof AssetStatus
            ? $data['asset_status']->value
            : ($data['asset_status'] ?? null);
        $data['department_id'] = $this->resolveSessionDepartmentId($request, $eventList, $inventoryList);
        $data['parent_asset_id'] = isset($data['parent_asset_id']) ? (int) $data['parent_asset_id'] : null;
        $data['replace_existing_parent'] = $request->boolean('replace_existing_parent');
        $this->assertParentAssetForDepartment($data['parent_asset_id'], $data['department_id']);

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

    protected function resolveSessionDepartmentId(Request $request, ?AssetList $eventList, ?AssetList $inventoryList): int
    {
        if ($eventList !== null) {
            return (int) $eventList->department_id;
        }

        if ($inventoryList !== null) {
            return (int) $inventoryList->department_id;
        }

        $departmentId = $request->user()?->resolveActiveDepartmentId();

        if ($departmentId === null) {
            throw ValidationException::withMessages([
                'event_list_id' => 'Select an active department before starting a scan session without lists.',
            ]);
        }

        return $departmentId;
    }

    protected function assertParentAssetForDepartment(?int $parentAssetId, int $departmentId): void
    {
        if ($parentAssetId === null) {
            return;
        }

        $parent = Asset::query()->find($parentAssetId);

        if ($parent === null || (int) $parent->department_id !== $departmentId) {
            throw ValidationException::withMessages([
                'parent_asset_id' => 'Parent asset must belong to the same department as this scan session.',
            ]);
        }
    }
}
