<?php

namespace App\Mcp\Tools;

use App\Enums\AssetStatus;
use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\AssetList;
use App\Models\ScanSession;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Start a stocktake scan session (closes any prior active session for the user).')]
class StartScanSessionTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'scan-sessions');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'event_list_id' => ['nullable', 'integer', Rule::exists('asset_lists', 'id')->where('type', 'event')],
            'inventory_list_id' => ['nullable', 'integer', Rule::exists('asset_lists', 'id')->where('type', 'inventory')],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'asset_status' => ['nullable', Rule::enum(AssetStatus::class)],
        ]);

        $eventList = isset($data['event_list_id'])
            ? AssetList::query()->find($data['event_list_id'])
            : null;
        $inventoryList = isset($data['inventory_list_id'])
            ? AssetList::query()->find($data['inventory_list_id'])
            : null;

        if ($eventList && $inventoryList && (int) $eventList->department_id !== (int) $inventoryList->department_id) {
            throw ValidationException::withMessages([
                'inventory_list_id' => 'Event and inventory lists must belong to the same department.',
            ]);
        }

        $departmentId = $eventList?->department_id
            ?? $inventoryList?->department_id
            ?? $user->resolveActiveDepartmentId();

        if ($departmentId === null) {
            throw ValidationException::withMessages([
                'event_list_id' => 'Select an active department before starting a scan session without lists.',
            ]);
        }

        ScanSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'closed']);

        $session = ScanSession::query()->create([
            'department_id' => $departmentId,
            'user_id' => $user->id,
            'event_list_id' => $data['event_list_id'] ?? null,
            'inventory_list_id' => $data['inventory_list_id'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'asset_status' => isset($data['asset_status']) && $data['asset_status'] instanceof AssetStatus
                ? $data['asset_status']->value
                : ($data['asset_status'] ?? null),
            'status' => 'active',
        ]);

        $session->load(['eventList:id,name,type', 'inventoryList:id,name,type', 'location:id,name']);

        return $this->jsonResult([
            'message' => 'Scan session started.',
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'department_id' => $session->department_id,
                'event_list_id' => $session->event_list_id,
                'inventory_list_id' => $session->inventory_list_id,
                'location_id' => $session->location_id,
                'asset_status' => $session->asset_status instanceof AssetStatus
                    ? $session->asset_status->value
                    : $session->asset_status,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event_list_id' => $schema->integer()->description('Optional event list to scan into.'),
            'inventory_list_id' => $schema->integer()->description('Optional inventory list for comparison status.'),
            'location_id' => $schema->integer()->description('Optional location to stamp on scans.'),
            'asset_status' => $schema->string()->description('Optional status to apply on scan (e.g. available, on_event).'),
        ];
    }
}
