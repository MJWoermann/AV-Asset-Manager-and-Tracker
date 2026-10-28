<?php

namespace App\Http\Controllers;

use App\Enums\LocationType;
use App\Http\Requests\BulkUpdateLocationsRequest;
use App\Http\Requests\DeleteLocationRequest;
use App\Models\Location;
use App\Services\BulkLocationUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $flatLocations = Location::query()
            ->with('parent.parent.parent.parent')
            ->withCount([
                'assets',
                'assets as unscoped_assets_count' => fn ($query) => $query->withoutGlobalScope('department'),
            ])
            ->orderByHierarchy()
            ->get();
        $locations = Location::tree($flatLocations);
        $parentOptions = Location::query()
            ->canBeParent()
            ->with('parent.parent.parent.parent')
            ->orderByHierarchy()
            ->get();
        $duplicateNames = $this->duplicateNormalizedNames($flatLocations);
        $canManageLocations = $request->user()?->can('locations:update') ?? false;

        $initialDeleteLocation = null;
        $deletingLocationId = (int) old('deleting_location_id');
        if ($canManageLocations && $deletingLocationId > 0) {
            $deletingLocation = $flatLocations->firstWhere('id', $deletingLocationId);
            if ($deletingLocation) {
                $initialDeleteLocation = [
                    'id' => $deletingLocation->id,
                    'name' => $deletingLocation->name,
                    'assetsCount' => (int) $deletingLocation->unscoped_assets_count,
                    'action' => route('locations.destroy', $deletingLocation),
                    'replacementId' => (string) old('replacement_location_id', ''),
                ];
            }
        }

        return view('locations.index', [
            'locations' => $locations,
            'flatLocations' => $flatLocations,
            'parentOptions' => $parentOptions,
            'duplicateNames' => $duplicateNames,
            'locationOptions' => $flatLocations
                ->map(fn (Location $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'room_code' => $location->room_code ?? '',
                    'label' => filled($location->room_code)
                        ? $location->breadcrumb().' ('.$location->room_code.')'
                        : $location->breadcrumb(),
                ])
                ->values(),
            'initialDeleteLocation' => $initialDeleteLocation,
        ]);
    }

    public function create(): View
    {
        return view('locations.form', [
            'location' => new Location(['type' => LocationType::Site]),
            'parents' => Location::parentOptions(),
            'types' => LocationType::cases(),
            'fileRows' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Location::create($this->validated($request));

        return redirect()->route('locations.index')->with('status', 'Location created.');
    }

    public function edit(Location $location): View
    {
        $location->load(['files', 'aliases']);

        $fileRows = $location->files->map(fn ($file): array => [
            'file' => $file,
            'inherited' => false,
            'link_id' => (int) $file->pivot->id,
            'note' => $file->pivot->note,
        ])->all();

        return view('locations.form', [
            'location' => $location,
            'parents' => Location::parentOptions((int) $location->id),
            'types' => LocationType::cases(),
            'fileRows' => $fileRows,
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $location->update($this->validated($request));

        return redirect()->route('locations.index')->with('status', 'Location updated.');
    }

    public function destroy(
        DeleteLocationRequest $request,
        Location $location,
        BulkLocationUpdateService $locations,
    ): RedirectResponse {
        if ($request->requiresReplacement()) {
            $replacement = Location::query()->findOrFail(
                (int) $request->validated('replacement_location_id')
            );
            $locations->deleteWithReplacement($location, $replacement);
        } else {
            $locations->deleteWithoutReassignment($location);
        }

        return redirect()->route('locations.index')->with('status', 'Location deleted.');
    }

    public function bulkUpdate(BulkUpdateLocationsRequest $request, BulkLocationUpdateService $bulkUpdate): RedirectResponse
    {
        $result = $bulkUpdate->handle($request->bulkPayload());

        return redirect()->route('locations.index')->with('status', $this->bulkStatusMessage($result));
    }

    /**
     * @param  array{updated: int, deleted: int, merged: int, assets_moved: int, kept?: int}  $result
     */
    protected function bulkStatusMessage(array $result): string
    {
        $parts = [];

        if ($result['merged'] > 0) {
            $parts[] = $result['merged'].' name group'.($result['merged'] === 1 ? '' : 's').' merged';
        }

        if ($result['assets_moved'] > 0) {
            $parts[] = $result['assets_moved'].' asset'.($result['assets_moved'] === 1 ? '' : 's').' reassigned';
        }

        if ($result['deleted'] > 0) {
            $parts[] = $result['deleted'].' location'.($result['deleted'] === 1 ? '' : 's').' deleted';
        }

        $kept = (int) ($result['kept'] ?? 0);
        if ($kept > 0) {
            $parts[] = $kept.' location'.($kept === 1 ? '' : 's').' kept as the replacement';
        }

        if ($result['updated'] > 0) {
            $parts[] = $result['updated'].' location'.($result['updated'] === 1 ? '' : 's').' updated';
        }

        return $parts === [] ? 'No changes applied.' : implode('. ', $parts).'.';
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return list<string>
     */
    protected function duplicateNormalizedNames(Collection $locations): array
    {
        return $locations
            ->groupBy(fn (Location $location): string => mb_strtolower(trim($location->name)))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->map(fn (Collection $group): string => $group->first()->name)
            ->sort()
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'room_code' => ['nullable', 'string', 'max:64'],
            'type' => ['required', Rule::enum(LocationType::class)],
            'parent_id' => [
                'nullable',
                Rule::exists('locations', 'id')->whereNot('type', LocationType::Rack->value),
            ],
            'is_portable' => ['sometimes', 'boolean'],
        ], [
            'parent_id.exists' => 'Racks cannot be a parent for another location.',
        ]) + ['is_portable' => $request->boolean('is_portable')];

        $data['room_code'] = filled($data['room_code'] ?? null) ? trim($data['room_code']) : null;

        return $data;
    }
}
