<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Guide an agent through a stocktake: start session, scan, compare, close.')]
class StocktakeFlowPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $eventListId = $request->get('event_list_id');
        $inventoryListId = $request->get('inventory_list_id');

        return Response::text(<<<TEXT
Stocktake flow for AV Asset Manager:
1. Call whoami (confirm the user is staff with scan and scan-sessions permissions; set_active_department if needed).
2. Optionally list_inventory_lists to pick event_list_id={$eventListId} and inventory_list_id={$inventoryListId}.
3. start_scan_session with those list ids.
4. For each barcode: scan_code. Note duplicates (warning, no double-insert) and unknown scans.
5. When finished: compare_lists(scanned_list_id=event, inventory_list_id=inventory).
6. close_scan_session.
7. For unknowns: list_unknown_scans then link_unknown_scan or create_asset.
Never mutate audit logs. Prefer compact JSON tool results over inventing data.
TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'event_list_id',
                description: 'Optional event list id to scan into.',
                required: false,
            ),
            new Argument(
                name: 'inventory_list_id',
                description: 'Optional inventory list id for comparison.',
                required: false,
            ),
        ];
    }
}
