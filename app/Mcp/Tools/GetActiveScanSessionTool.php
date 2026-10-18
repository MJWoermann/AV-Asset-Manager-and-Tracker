<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\ScanSession;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get the caller\'s current active scan session, if any.')]
class GetActiveScanSessionTool extends Tool
{
    use AuthorizesMcpTools;

    public function handle(Request $request): Response
    {
        $user = $this->requireMcpUser($request);
        if ($user instanceof Response) {
            return $user;
        }

        $session = ScanSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['eventList:id,name,type', 'inventoryList:id,name,type', 'location:id,name'])
            ->latest()
            ->first();

        if ($session === null) {
            return $this->jsonResult(['active' => false, 'session' => null]);
        }

        return $this->jsonResult([
            'active' => true,
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'department_id' => $session->department_id,
                'event_list_id' => $session->event_list_id,
                'event_list' => $session->eventList?->name,
                'inventory_list_id' => $session->inventory_list_id,
                'inventory_list' => $session->inventoryList?->name,
                'location_id' => $session->location_id,
                'location' => $session->location?->name,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
