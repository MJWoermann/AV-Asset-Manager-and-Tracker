<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\FindMissingAssetPrompt;
use App\Mcp\Prompts\StocktakeFlowPrompt;
use App\Mcp\Resources\GlossaryResource;
use App\Mcp\Resources\MeResource;
use App\Mcp\Tools\AssetHistoryTool;
use App\Mcp\Tools\CheckoutAssetTool;
use App\Mcp\Tools\CloseScanSessionTool;
use App\Mcp\Tools\CompareListsTool;
use App\Mcp\Tools\CreateAssetTool;
use App\Mcp\Tools\CreateRepairTool;
use App\Mcp\Tools\DashboardStatsTool;
use App\Mcp\Tools\GetActiveScanSessionTool;
use App\Mcp\Tools\GetRepairTool;
use App\Mcp\Tools\LinkUnknownScanTool;
use App\Mcp\Tools\ListInventoryListsTool;
use App\Mcp\Tools\ListRepairsTool;
use App\Mcp\Tools\ListUnknownScansTool;
use App\Mcp\Tools\LookupAssetTool;
use App\Mcp\Tools\LookupSpareTool;
use App\Mcp\Tools\RecordSpareMovementTool;
use App\Mcp\Tools\ReturnAssetTool;
use App\Mcp\Tools\ScanCodeTool;
use App\Mcp\Tools\SearchAssetsTool;
use App\Mcp\Tools\SetActiveDepartmentTool;
use App\Mcp\Tools\StartScanSessionTool;
use App\Mcp\Tools\UpdateAssetTool;
use App\Mcp\Tools\UpdateRepairStatusTool;
use App\Mcp\Tools\WhoamiTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('AV Asset Manager')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
# AV Asset Manager MCP (Copilot Studio)

Online-only PWA for AV equipment stocktake and asset management.

## Auth & roles
- Copilot Studio connects with **Laravel Passport OAuth** (`auth:api`, scope `mcp:use`) at `/mcp/av-assets`.
- Sanctum Bearer tokens remain for the REST API under `/api/*` (not this MCP endpoint).
- Staff with operational permissions, including custom roles. Each tool checks the same permission as the matching web and REST route. **external_repairer is denied**.
- Call `whoami` first; use `set_active_department` when creating department-scoped records.

## Glossary
- **Asset** — tracked equipment; children inherit location from a parent (e.g. gear in a rack).
- **Inventory list** — expected stock; **event list** — scanned/usage for a stocktake.
- **Scan session** — active scanning into an event list, optionally compared to inventory.
- **Unknown scan** — unmatched barcode parked for follow-up; link with `link_unknown_scan`.
- **Checkout** — hire / repair / other hand-off via `checkout_asset` / `return_asset`. The REST API uses the same service and requires `assets:write`.
- **Repair / spare** — maintenance workflow; spares movements via `record_spare_movement`.
- **Audit log** — immutable append-only history. Tools may **read** via `asset_history` but must **never update or delete** audit logs.

## Stocktake recipe
1. `start_scan_session` (optional event + inventory lists)
2. `scan_code` repeatedly
3. `compare_lists` when done
4. `close_scan_session`

## Response style
- Tools return compact JSON text. Prefer identifiers (id, fmi_ast, tp_barcode) over prose.
- Respect rate limits on the HTTP transport; avoid tight scan loops without need.
MARKDOWN)]
class AvAssetManagerServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        WhoamiTool::class,
        SetActiveDepartmentTool::class,
        DashboardStatsTool::class,
        LookupAssetTool::class,
        SearchAssetsTool::class,
        AssetHistoryTool::class,
        ListInventoryListsTool::class,
        CompareListsTool::class,
        StartScanSessionTool::class,
        GetActiveScanSessionTool::class,
        ScanCodeTool::class,
        CloseScanSessionTool::class,
        ListUnknownScansTool::class,
        LinkUnknownScanTool::class,
        CreateAssetTool::class,
        UpdateAssetTool::class,
        CheckoutAssetTool::class,
        ReturnAssetTool::class,
        ListRepairsTool::class,
        GetRepairTool::class,
        CreateRepairTool::class,
        UpdateRepairStatusTool::class,
        LookupSpareTool::class,
        RecordSpareMovementTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        GlossaryResource::class,
        MeResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        StocktakeFlowPrompt::class,
        FindMissingAssetPrompt::class,
    ];
}
