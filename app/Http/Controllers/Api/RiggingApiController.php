<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesRigEvents;
use App\Http\Controllers\Controller;
use App\Models\RigEvent;
use App\Models\RiggingCalculationLog;
use App\Services\Rigging\RiggingCalculatorRegistry;
use App\Support\RiggingCalculators;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RiggingApiController extends Controller
{
    use ManagesRigEvents;

    public function __construct(private RiggingCalculatorRegistry $calculators) {}

    public function eventsIndex(Request $request): JsonResponse
    {
        $events = RigEvent::query()->orderByDesc('event_date')->paginate(25);

        return response()->json($events);
    }

    public function eventsShow(RigEvent $event): JsonResponse
    {
        $event->load(['motorPatches', 'calculationLogs']);

        return response()->json($event);
    }

    public function eventsStore(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;
        $data['process_checklist'] = $data['process_checklist'] ?? $this->emptyChecklist();

        $event = RigEvent::query()->create($data);

        return response()->json($event, 201);
    }

    public function calculatorsIndex(): JsonResponse
    {
        return response()->json(['data' => $this->calculators->catalog()]);
    }

    public function calculatorsCompute(Request $request, string $calculator): JsonResponse
    {
        if (! RiggingCalculators::isValid($calculator)) {
            abort(404);
        }

        try {
            $payload = $this->calculators->compute($calculator, $request->input('inputs', []));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function storeCalculationLog(Request $request, string $calculator): JsonResponse
    {
        if (! RiggingCalculators::isValid($calculator)) {
            abort(404);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'inputs' => ['required', 'array'],
            'rig_event_id' => ['nullable', 'integer', 'exists:rig_events,id'],
        ]);

        $computed = $this->calculators->compute($calculator, $data['inputs']);

        $log = RiggingCalculationLog::query()->create([
            'calculator' => $calculator,
            'title' => $data['title'] ?? RiggingCalculators::label($calculator),
            'inputs' => $data['inputs'],
            'outputs' => $computed,
            'rig_event_id' => $data['rig_event_id'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($log, 201);
    }
}
