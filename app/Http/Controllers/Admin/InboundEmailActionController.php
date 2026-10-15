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
        $allowed = array_merge(['all'], array_keys(InboundEmailActionStatus::options()));
        if (! in_array($status, $allowed, true)) {
            $status = InboundEmailActionStatus::Pending->value;
        }

        $actions = InboundEmailAction::query()
            ->with(['asset', 'rule'])
            ->when(
                $status !== 'all',
                fn ($query) => $query->where('status', $status),
            )
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.email.inbound.index', [
            'actions' => $actions,
            'statuses' => InboundEmailActionStatus::options(),
            'status' => $status,
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
