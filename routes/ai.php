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
| Dynamic client registration: POST /oauth/register (see config/mcp.php
| redirect_domains — allow Copilot Studio redirect URIs in production).
|
*/

Mcp::oauthRoutes();

Mcp::web('/mcp/av-assets', AvAssetManagerServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
