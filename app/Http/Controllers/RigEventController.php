<?php

namespace App\Http\Controllers;

use App\Enums\FileCategory;
use App\Enums\RigEventStatus;
use App\Http\Controllers\Concerns\ManagesRigEvents;
use App\Models\RigEvent;
use App\Models\RiggingCalculationLog;
use App\Services\FileAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RigEventController extends Controller
{
    use ManagesRigEvents;

    public function index(Request $request): View
    {
        $events = RigEvent::query()
            ->with(['creator:id,name', 'assetList:id,name,type'])
            ->withCount(['motorPatches', 'calculationLogs'])
            ->when($request->string('status')->toString(), function ($q, string $status) {
                $q->where('status', $status);
            })
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('rigging.events.index', [
            'events' => $events,
            'statuses' => RigEventStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('rigging.events.form', [
            'event' => new RigEvent,
            'statuses' => RigEventStatus::options(),
            'eventLists' => $this->eventLists(),
            'fileRows' => [],
            'checklist' => $this->emptyChecklist(),
            'motorPatches' => [],
            'calculationLogs' => collect(),
            'unlinkedLogs' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;
        $data['process_checklist'] = $data['process_checklist'] ?? $this->emptyChecklist();

        $event = RigEvent::query()->create($data);

        return redirect()
            ->route('rigging.events.show', $event)
            ->with('status', 'Rig Event created.');
    }

    public function show(RigEvent $event): View
    {
        return $this->edit($event);
    }

    public function edit(RigEvent $event): View
    {
        $event->load([
            'files',
            'assetList:id,name,type',
            'motorPatches',
            'calculationLogs.creator:id,name',
            'creator:id,name',
        ]);

        $fileRows = $event->files->map(fn ($file): array => [
            'file' => $file,
            'inherited' => false,
            'link_id' => (int) $file->pivot->id,
            'note' => $file->pivot->note,
        ])->all();

        $motorPatches = old('motor_patches');
        if ($motorPatches === null) {
            $motorPatches = $event->motorPatches->map(fn ($row) => [
                'id' => $row->id,
                'motor_label' => $row->motor_label,
                'patch_to' => $row->patch_to,
                'notes' => $row->notes,
                'sort_order' => $row->sort_order,
            ])->values()->all();
        }

        $checklist = old('process_checklist', $event->process_checklist ?: $this->emptyChecklist());

        return view('rigging.events.form', [
            'event' => $event,
            'statuses' => RigEventStatus::options(),
            'eventLists' => $this->eventLists(),
            'fileRows' => $fileRows,
            'checklist' => $checklist,
            'motorPatches' => $motorPatches,
            'calculationLogs' => $event->calculationLogs,
            'unlinkedLogs' => RiggingCalculationLog::query()
                ->with('creator:id,name')
                ->whereNull('rig_event_id')
                ->latest()
                ->limit(40)
                ->get(),
            'categories' => FileCategory::options(),
        ]);
    }

    public function update(Request $request, RigEvent $event): RedirectResponse
    {
        $event->update($this->validated($request, $event));

        return redirect()
            ->route('rigging.events.show', $event)
            ->with('status', 'Rig Event updated.');
    }

    public function destroy(RigEvent $event): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'inventory_manager']), 403);

        $event->delete();

        return redirect()
            ->route('rigging.events.index')
            ->with('status', 'Rig Event deleted.');
    }

    public function updateChecklist(Request $request, RigEvent $event): RedirectResponse
    {
        $keys = RigEvent::checklistKeys();
        $data = $request->validate([
            'process_checklist' => ['nullable', 'array'],
            'process_checklist.*' => ['boolean'],
        ]);

        $checklist = $this->emptyChecklist();
        foreach ($keys as $key) {
            $checklist[$key] = (bool) ($data['process_checklist'][$key] ?? false);
        }

        $event->update(['process_checklist' => $checklist]);

        return back()->with('status', 'Checklist updated.');
    }

    public function syncMotorPatches(Request $request, RigEvent $event): RedirectResponse
    {
        $data = $request->validate([
            'motor_patches' => ['nullable', 'array'],
            'motor_patches.*.motor_label' => ['required', 'string', 'max:255'],
            'motor_patches.*.patch_to' => ['nullable', 'string', 'max:255'],
            'motor_patches.*.notes' => ['nullable', 'string'],
            'motor_patches.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $rows = $data['motor_patches'] ?? [];

        DB::transaction(function () use ($event, $rows): void {
            $event->motorPatches()->delete();

            foreach (array_values($rows) as $index => $row) {
                $label = trim((string) ($row['motor_label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                $event->motorPatches()->create([
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                    'motor_label' => $label,
                    'patch_to' => $row['patch_to'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            }
        });

        return back()->with('status', 'Motor patching saved.');
    }

    public function signOff(Request $request, RigEvent $event, FileAttachmentService $attachments): RedirectResponse
    {
        $data = $request->validate([
            'structure_signed_name' => ['required', 'string', 'max:255'],
            'structure_notes' => ['nullable', 'string'],
            'signature_data' => ['nullable', 'string'],
            'replace' => ['sometimes', 'boolean'],
        ]);

        if ($event->isSignedOff() && ! $request->boolean('replace')) {
            return back()->withErrors([
                'structure_signed_name' => 'Already signed off. Confirm replace to overwrite.',
            ]);
        }

        $event->update([
            'structure_signed_name' => $data['structure_signed_name'],
            'structure_signed_at' => now(),
            'structure_notes' => $data['structure_notes'] ?? null,
            'status' => RigEventStatus::SignedOff,
        ]);

        if (! empty($data['signature_data']) && str_starts_with($data['signature_data'], 'data:image/png;base64,')) {
            $binary = base64_decode(substr($data['signature_data'], strlen('data:image/png;base64,')), true);
            if ($binary !== false) {
                $tmp = tempnam(sys_get_temp_dir(), 'rigsig');
                file_put_contents($tmp, $binary);
                $upload = new UploadedFile($tmp, 'structure-signature.png', 'image/png', null, true);
                $attachments->storeUpload($upload, [
                    'title' => 'Structure sign-off — '.$event->name,
                    'category' => FileCategory::RiggingPlan->value,
                    'note' => 'structure_signature',
                ], $event);
                @unlink($tmp);
            }
        }

        return back()->with('status', 'Structure sign-off recorded.');
    }
}
