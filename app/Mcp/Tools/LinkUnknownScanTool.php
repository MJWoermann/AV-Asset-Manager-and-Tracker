<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use App\Models\UnknownScan;
use App\Services\ScanService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Link an unknown scan to an existing asset (optionally set identifier / add to event list).')]
class LinkUnknownScanTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private ScanService $scanService) {}

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'reports:write');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'unknown_scan_id' => ['required', 'integer', 'exists:unknown_scans,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'add_to_event_list' => ['nullable', 'boolean'],
            'set_identifier' => ['nullable', 'boolean'],
        ]);

        $unknownScan = UnknownScan::query()->findOrFail((int) $data['unknown_scan_id']);
        $asset = Asset::query()->findOrFail((int) $data['asset_id']);

        $unknownScan->linkToExisting(
            $asset,
            (bool) ($data['add_to_event_list'] ?? false),
            (bool) ($data['set_identifier'] ?? false),
            $user->id,
        );

        return $this->jsonResult([
            'message' => 'Unknown scan linked to existing asset.',
            'unknown_scan' => $this->scanService->unknownScanSummary(
                $unknownScan->fresh(['resolvedAsset'])
            ),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'unknown_scan_id' => $schema->integer()->description('Unknown scan id.')->required(),
            'asset_id' => $schema->integer()->description('Existing asset id to link.')->required(),
            'add_to_event_list' => $schema->boolean()->description('Also add the asset to the event list if present.'),
            'set_identifier' => $schema->boolean()->description('Copy the scanned code onto an empty asset identifier field.'),
        ];
    }
}
