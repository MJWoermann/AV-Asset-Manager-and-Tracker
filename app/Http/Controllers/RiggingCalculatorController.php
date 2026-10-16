<?php

namespace App\Http\Controllers;

use App\Models\ItemType;
use App\Models\RigEvent;
use App\Models\RiggingCalculationLog;
use App\Services\Rigging\RiggingCalculatorRegistry;
use App\Support\RiggingCalculators;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RiggingCalculatorController extends Controller
{
    public function __construct(private RiggingCalculatorRegistry $calculators) {}

    public function index(): View
    {
        return view('rigging.calculators.index', [
            'calculators' => $this->calculators->catalog(),
        ]);
    }

    public function show(Request $request, string $calculator): View
    {
        if (! RiggingCalculators::isValid($calculator)) {
            abort(404);
        }

        $logs = RiggingCalculationLog::query()
            ->with(['creator:id,name', 'rigEvent:id,name'])
            ->where('calculator', $calculator)
            ->latest()
            ->limit(50)
            ->get();

        $events = RigEvent::query()
            ->whereIn('status', ['draft', 'in_progress'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'name', 'event_date', 'venue_space']);

        $itemTypes = ItemType::query()
            ->where('supports_rigging_calculator', true)
            ->orderBy('name')
            ->get(['id', 'name', 'rigging_weight_kg']);

        return view('rigging.calculators.show', [
            'calculator' => $calculator,
            'label' => RiggingCalculators::label($calculator),
            'logs' => $logs,
            'events' => $events,
            'itemTypes' => $itemTypes,
            'computeUrl' => route('rigging.calculators.compute', $calculator),
            'saveUrl' => route('rigging.calculators.logs.store', $calculator),
        ]);
    }

    /**
     * Live preview compute (JSON) — does not persist.
     */
    public function compute(Request $request, string $calculator)
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
}
