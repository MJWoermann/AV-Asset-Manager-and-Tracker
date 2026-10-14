<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InboundEmailActionStatus;
use App\Http\Controllers\Controller;
use App\Models\InboundEmailAction;
use App\Services\InboundEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboundEmailActionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $actions = InboundEmailAction::query()
            ->with(['asset', 'rule'])
            ->when(
                $status !== '' && array_key_exists($status, InboundEmailActionStatus::options()),
                fn ($query) => $query->where('status', $status),
                fn ($query) => $query->where('status', InboundEmailActionStatus::Pending->value),
            )
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.email.inbound.index', [
            'actions' => $actions,
            'statuses' => InboundEmailActionStatus::options(),
            'status' => $status !== '' ? $status : InboundEmailActionStatus::Pending->value,
        ]);
    }

    public function apply(InboundEmailAction $inboundEmailAction, InboundEmailService $inbound): RedirectResponse
    {
        if ($inboundEmailAction->status !== InboundEmailActionStatus::Pending) {
            return back()->withErrors(['action' => 'Only pending actions can be applied.']);
        }

        $inbound->applyAction($inboundEmailAction, request()->user());

        return back()->with('status', 'Inbound email applied to maintenance log.');
    }

    public function reject(InboundEmailAction $inboundEmailAction, InboundEmailService $inbound): RedirectResponse
    {
        if ($inboundEmailAction->status !== InboundEmailActionStatus::Pending) {
            return back()->withErrors(['action' => 'Only pending actions can be rejected.']);
        }

        $inbound->rejectAction($inboundEmailAction, request()->user());

        return back()->with('status', 'Inbound email rejected.');
    }
}
