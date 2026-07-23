<?php

namespace Tests\Feature\Mcp;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The MCP server is protected with OAuth 2.1 via Passport. These tests assert
 * the auth boundary: discovery is public, the server rejects unauthenticated
 * calls, and the OAuth authorization endpoint exists.
 */
class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_protected_resource_discovery_document_is_public(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJsonStructure(['resource', 'authorization_servers', 'scopes_supported'])
            ->assertJsonFragment(['scopes_supported' => ['mcp:use']]);
    }

    public function test_the_authorization_server_discovery_document_is_public(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint']);
    }

    public function test_the_mcp_server_rejects_unauthenticated_requests(): void
    {
        $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ])->assertUnauthorized();
    }
}
