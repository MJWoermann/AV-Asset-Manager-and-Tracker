<?php

use App\Mcp\Servers\AvAssetManagerServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP / Copilot Studio
|--------------------------------------------------------------------------
|
| Passport OAuth (auth:api) is the primary auth for Copilot Studio clients.
| Sanctum Bearer tokens remain for the REST API under /api/* — dual auth on
| this MCP route is not registered (Passport-primary). Use Passport::actingAs
| or Mcp server actingAs() in tests; issue OAuth tokens for live Copilot use.
|
| Local keys: php artisan passport:keys
| OAuth discovery: /.well-known/oauth-authorization-server
| Dynamic client registration: POST /oauth/register
| Set MCP_REDIRECT_DOMAINS to the Copilot Studio redirect origins.
| An empty list refuses every dynamic client redirect.
|
*/

Mcp::oauthRoutes();

Mcp::web('/mcp/av-assets', AvAssetManagerServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
