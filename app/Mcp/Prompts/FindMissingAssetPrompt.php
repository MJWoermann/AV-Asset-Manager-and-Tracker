<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Help find a missing asset: search, history, list compare, unknown scans.')]
class FindMissingAssetPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $query = (string) ($request->get('query') ?? '');

        return Response::text(<<<TEXT
Find missing asset (query: {$query}):
1. search_assets / lookup_asset with the query or barcode.
2. If found: asset_history for recent location/status/checkout changes; check open repairs with list_repairs.
3. If not found: list_unknown_scans for the code; compare_lists for the latest event vs inventory (inventory_only section).
4. Check checkout: if on hire/repair, use get_repair / note open checkout context from history actions.
Do not invent identifiers. Prefer tool results. Never update/delete audit logs.
TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'query',
                description: 'Name, barcode, or identifier to hunt for.',
                required: false,
            ),
        ];
    }
}
