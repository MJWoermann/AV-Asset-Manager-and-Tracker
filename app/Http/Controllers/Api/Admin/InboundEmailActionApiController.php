<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InboundEmailActionStatus;
use App\Http\Controllers\Controller;
use App\Models\InboundEmailAction;
use App\Services\InboundEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundEmailActionApiController extends Controller
{
    public function index(Request $request): JsonResponse
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
            ->paginate(50);

        return response()->json($actions);
    }

    public function apply(InboundEmailAction $inboundEmailAction, InboundEmailService $inbound): JsonResponse
    {
        if ($inboundEmailAction->status !== InboundEmailActionStatus::Pending) {
            return response()->json(['message' => 'Only pending actions can be applied.'], 422);
        }

        $inbound->applyAction($inboundEmailAction, request()->user());

        return response()->json(['message' => 'Inbound email applied to maintenance log.']);
    }

    public function reject(InboundEmailAction $inboundEmailAction, InboundEmailService $inbound): JsonResponse
    {
        if ($inboundEmailAction->status !== InboundEmailActionStatus::Pending) {
            return response()->json(['message' => 'Only pending actions can be rejected.'], 422);
        }

        $inbound->rejectAction($inboundEmailAction, request()->user());

        return response()->json(['message' => 'Inbound email rejected.']);
    }
}
