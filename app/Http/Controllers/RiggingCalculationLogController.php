<?php

namespace App\Http\Controllers;

use App\Models\RigEvent;
use App\Models\RiggingCalculationLog;
use App\Services\Rigging\RiggingCalculatorRegistry;
use App\Support\RiggingCalculators;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RiggingCalculationLogController extends Controller
{
    public function __construct(private RiggingCalculatorRegistry $calculators) {}

    public function store(Request $request, string $calculator): RedirectResponse
    {
        if (! RiggingCalculators::isValid($calculator)) {
            abort(404);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'inputs' => ['required'],
            'rig_event_id' => ['nullable', 'integer', 'exists:rig_events,id'],
        ]);

        $inputs = $data['inputs'];
        if (is_string($inputs)) {
            $decoded = json_decode($inputs, true);
            if (! is_array($decoded)) {
                return back()->withInput()->withErrors(['inputs' => 'Invalid inputs payload.']);
            }
            $inputs = $decoded;
        }

        if (! is_array($inputs)) {
            return back()->withInput()->withErrors(['inputs' => 'Inputs must be an object.']);
        }

        try {
            $computed = $this->calculators->compute($calculator, $inputs);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['inputs' => $e->getMessage()]);
        }

        RiggingCalculationLog::query()->create([
            'calculator' => $calculator,
            'title' => $data['title'] ?? RiggingCalculators::label($calculator),
            'inputs' => $computed['inputs'],
            'results' => $computed['results'],
            'units' => $computed['units'],
            'rig_event_id' => $data['rig_event_id'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Calculation saved to team log.');
    }

    public function attach(Request $request, RigEvent $event): RedirectResponse
    {
        $data = $request->validate([
            'rigging_calculation_log_id' => ['required', 'integer', 'exists:rigging_calculation_logs,id'],
        ]);

        $log = RiggingCalculationLog::query()->findOrFail($data['rigging_calculation_log_id']);

        if ($log->rig_event_id !== null && (int) $log->rig_event_id !== (int) $event->id) {
            return back()->withErrors([
                'rigging_calculation_log_id' => 'That calculation is already linked to another Rig Event.',
            ]);
        }

        $log->syncRigEvent((int) $event->id);

        return back()->with('status', 'Calculation attached to Rig Event.');
    }

    public function detach(RigEvent $event, RiggingCalculationLog $log): RedirectResponse
    {
        abort_unless((int) $log->rig_event_id === (int) $event->id, 404);

        $log->syncRigEvent(null);

        return back()->with('status', 'Calculation detached from Rig Event.');
    }
}
