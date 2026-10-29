<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Models\Asset;
use App\Services\AssetCheckoutService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Check out an asset via AssetCheckoutService (hire, repair, other, or another active checkout option).')]
class CheckoutAssetTool extends Tool
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
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'purpose' => [
                'required',
                'string',
                'max:32',
                Rule::exists('checkout_purposes', 'slug')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'checked_out_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'checked_out_to_name' => ['nullable', 'string', 'max:255'],
            'checked_out_to_email' => ['nullable', 'email', 'max:255'],
            'checked_out_to_company' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'expected_return_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'repair_id' => ['nullable', 'integer', 'exists:repairs,id'],
            'create_repair' => ['nullable', 'boolean'],
            'repair_summary' => ['nullable', 'string', 'max:255'],
            'repair_notes' => ['nullable', 'string'],
        ]);

        $asset = Asset::query()->findOrFail((int) $data['asset_id']);
        unset($data['asset_id']);

        $checkout = $this->checkouts->checkout($asset, $data, $user);

        return $this->jsonResult([
            'message' => 'Asset checked out.',
            'checkout' => [
                'id' => $checkout->id,
                'asset_id' => $checkout->asset_id,
                'purpose' => (string) $checkout->purpose,
                'checked_out_to_user_id' => $checkout->checked_out_to_user_id,
                'checked_out_to_name' => $checkout->checked_out_to_name,
                'repair_id' => $checkout->repair_id,
                'expected_return_at' => optional($checkout->expected_return_at)?->toIso8601String(),
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset_id' => $schema->integer()->description('Asset to check out.')->required(),
            'purpose' => $schema->string()->description('Active checkout option slug, such as hire, repair, or other.')->required(),
            'checked_out_to_user_id' => $schema->integer()->description('Assignee user id (or provide guest name).'),
            'checked_out_to_name' => $schema->string()->description('Guest name when no user id.'),
            'checked_out_to_email' => $schema->string(),
            'checked_out_to_company' => $schema->string(),
            'location_id' => $schema->integer(),
            'expected_return_at' => $schema->string()->description('ISO date/time.'),
            'notes' => $schema->string(),
            'repair_id' => $schema->integer()->description('Link an existing repair (purpose=repair).'),
            'create_repair' => $schema->boolean()->description('Create a new repair when purpose=repair.'),
            'repair_summary' => $schema->string(),
            'repair_notes' => $schema->string(),
        ];
    }
}
