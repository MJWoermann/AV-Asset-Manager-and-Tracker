<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('av://glossary')]
#[Description('Domain glossary for AV Asset Manager (asset, lists, scan, checkout, repair, audit).')]
class GlossaryResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::text(<<<'TEXT'
AV Asset Manager glossary:
- Asset: tracked equipment item; parent/child racks expand children on scan.
- Item type: template for assets (custom field sets attach here).
- Inventory list: expected stock. Event list: scanned/usage for a stocktake.
- Scan session: active user session scanning into an event list.
- Unknown scan: unmatched barcode parked for follow-up; link to an existing asset.
- Checkout: hire/repair/other hand-off; return may be blocked while linked repair is open.
- Repair / procedure / spare: maintenance workflow; spare movements adjust on-hand qty.
- Department: multi-department isolation; set active department for creates.
- Audit log: immutable append-only history (read via asset_history; never mutate).
TEXT);
    }
}
