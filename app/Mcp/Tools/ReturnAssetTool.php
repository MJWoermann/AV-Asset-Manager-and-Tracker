<?php

namespace App\Mcp\Tools;

use App\Enums\AssetStatus;
use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Services\AssetCheckoutService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Return an open asset checkout via AssetCheckoutService.')]
class ReturnAssetTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private AssetCheckoutService $checkouts) {}

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'assets:write');
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'checkout_id' => ['nullable', 'integer', 'exists:asset_checkouts,id'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'notes' => ['nullable', 'string'],
            'force' => ['nullable', 'boolean'],
        ]);

        $checkout = null;
        if (! empty($data['checkout_id'])) {
            $checkout = AssetCheckout::query()->findOrFail((int) $data['checkout_id']);
        } elseif (! empty($data['asset_id'])) {
            $checkout = AssetCheckout::query()
                ->where('asset_id', (int) $data['asset_id'])
                ->open()
                ->latest('id')
                ->first();
        }

        if ($checkout === null) {
            return Response::error('Provide checkout_id or asset_id with an open checkout.');
        }

        $returned = $this->checkouts->returnCheckout($checkout, $user, [
            'notes' => $data['notes'] ?? null,
            'force' => (bool) ($data['force'] ?? false),
        ]);

        $asset = Asset::query()->find($returned->asset_id);

        return $this->jsonResult([
            'message' => 'Asset returned.',
            'checkout' => [
                'id' => $returned->id,
                'asset_id' => $returned->asset_id,
                'returned_at' => optional($returned->returned_at)?->toIso8601String(),
            ],
            'asset_status' => $asset?->status instanceof AssetStatus
                ? $asset->status->value
                : $asset?->status,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'checkout_id' => $schema->integer()->description('Open checkout id.'),
            'asset_id' => $schema->integer()->description('Asset id (uses latest open checkout).'),
            'notes' => $schema->string(),
            'force' => $schema->boolean()->description('Force return even if linked repair is still open.'),
        ];
    }
}
