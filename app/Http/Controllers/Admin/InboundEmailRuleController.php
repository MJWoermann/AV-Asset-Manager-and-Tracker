<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetStatus;
use App\Enums\InboundEmailRuleAction;
use App\Enums\MaintenanceLogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInboundEmailRuleRequest;
use App\Http\Requests\Admin\UpdateInboundEmailRuleRequest;
use App\Models\InboundEmailRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InboundEmailRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.email.rules.index', [
            'rules' => InboundEmailRule::query()->orderBy('priority')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.email.rules.form', $this->formData(new InboundEmailRule([
            'is_active' => true,
            'priority' => 100,
            'action' => InboundEmailRuleAction::CreateLog,
            'log_type' => MaintenanceLogType::Note,
            'require_review' => true,
            'match_conditions' => [],
        ])));
    }

    public function store(StoreInboundEmailRuleRequest $request): RedirectResponse
    {
        InboundEmailRule::query()->create($request->validatedRule());

        return redirect()
            ->route('admin.email.rules.index')
            ->with('status', 'Inbound rule created.');
    }

    public function edit(InboundEmailRule $rule): View
    {
        return view('admin.email.rules.form', $this->formData($rule));
    }

    public function update(UpdateInboundEmailRuleRequest $request, InboundEmailRule $rule): RedirectResponse
    {
        $rule->update($request->validatedRule());

        return redirect()
            ->route('admin.email.rules.index')
            ->with('status', 'Inbound rule updated.');
    }

    public function destroy(InboundEmailRule $rule): RedirectResponse
    {
        $rule->delete();

        return redirect()
            ->route('admin.email.rules.index')
            ->with('status', 'Inbound rule deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(InboundEmailRule $rule): array
    {
        $conditions = is_array($rule->match_conditions) ? $rule->match_conditions : [];

        return [
            'rule' => $rule,
            'actions' => InboundEmailRuleAction::options(),
            'logTypes' => MaintenanceLogType::options(),
            'statuses' => AssetStatus::options(),
            'subjectContains' => $conditions['subject_contains'] ?? '',
            'fromContains' => $conditions['from_contains'] ?? '',
            'bodyContains' => $conditions['body_contains'] ?? '',
        ];
    }
}
