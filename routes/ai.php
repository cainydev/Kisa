<?php

use App\Mcp\Servers\KisServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Server
|--------------------------------------------------------------------------
|
| The KISA MCP server exposes the domain (herbs, suppliers, certificates,
| deliveries, products, variants) to LLM chatbot clients. It is protected with
| OAuth 2.1 via Laravel Passport: oauthRoutes() advertises the OAuth discovery
| and dynamic client-registration endpoints, and the server itself is behind
| the Passport "api" guard. This is the auth mechanism MCP clients (e.g. Claude
| Desktop) support out of the box.
|
*/

Mcp::oauthRoutes();

Mcp::web('/mcp', KisServer::class)
    ->middleware('auth:api');
