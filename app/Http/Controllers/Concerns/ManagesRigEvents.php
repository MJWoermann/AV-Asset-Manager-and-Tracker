<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\RigEventStatus;
use App\Models\AssetList;
use App\Models\RigEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

trait ManagesRigEvents
{
    /** @return array<string, mixed> */
    protected function validated(Request $request, ?RigEvent $event = null): array
    {
        if ($request->has('engineer_required')) {
            $request->merge(['engineer_required' => $request->boolean('engineer_required')]);
        }
        if ($request->has('engineer_acquired')) {
            $request->merge(['engineer_acquired' => $request->boolean('engineer_acquired')]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'venue_space' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(RigEventStatus::class)],
            'engineer_required' => ['sometimes', 'boolean'],
            'engineer_acquired' => ['sometimes', 'boolean'],
            'engineer_notes' => ['nullable', 'string'],
            'variations_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'asset_list_id' => ['nullable', 'integer', 'exists:asset_lists,id'],
            'process_checklist' => ['nullable', 'array'],
            'process_checklist.*' => ['boolean'],
        ]);

        if (isset($data['process_checklist'])) {
            $checklist = $this->emptyChecklist();
            foreach (RigEvent::checklistKeys() as $key) {
                $checklist[$key] = (bool) ($data['process_checklist'][$key] ?? false);
            }
            $data['process_checklist'] = $checklist;
        }

        $data['engineer_required'] = (bool) ($data['engineer_required'] ?? false);
        $data['engineer_acquired'] = (bool) ($data['engineer_acquired'] ?? false);

        return $data;
    }

    /** @return array<string, bool> */
    protected function emptyChecklist(): array
    {
        $checklist = [];
        foreach (RigEvent::checklistKeys() as $key) {
            $checklist[$key] = false;
        }

        return $checklist;
    }

    /** @return Collection<int, AssetList> */
    protected function eventLists()
    {
        return AssetList::query()
            ->where('type', 'event')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
    }
}
