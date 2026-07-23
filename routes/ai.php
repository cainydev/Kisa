<?php

use App\Mcp\Servers\KisServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Server
|--------------------------------------------------------------------------
|
| The KISA MCP server exposes the domain (herbs, suppliers, certificates,
| deliveries, products, variants) to LLM chatbot clients. It is authenticated
| with Sanctum: clients pass a personal access token (see /api/tokens/create)
| in the Authorization header.
|
*/

Mcp::web('/mcp', KisServer::class)
    ->middleware('auth:sanctum');
