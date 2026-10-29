<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\AuthorizesMcpTools;
use App\Services\DashboardStatsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Return compact dashboard stats for the authenticated user (asset counts, open repairs, unknown scans, etc.).')]
class DashboardStatsTool extends Tool
{
    use AuthorizesMcpTools;

    public function __construct(private DashboardStatsService $stats) {}

    public function handle(Request $request): Response
    {
        $user = $this->requirePermission($request, 'reports:read');
        if ($user instanceof Response) {
            return $user;
        }

        return $this->jsonResult($this->stats->stats($user));
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
