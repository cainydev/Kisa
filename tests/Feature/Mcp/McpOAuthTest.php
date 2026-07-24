<?php

namespace Tests\Feature\Mcp;

use Filament\Facades\Filament;
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

    public function test_the_authorize_endpoint_redirects_guests_to_the_panel_login(): void
    {
        $client = app('Laravel\Passport\ClientRepository')->createAuthorizationCodeGrantClient(
            name: 'Test Client',
            redirectUris: ['https://example.com/callback'],
            confidential: false,
        );

        $this->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => 'https://example.com/callback',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'state',
            'code_challenge' => 'fC299vdQkeMU7uADK8_jZYfBGeGpsOJ4Hh3CN5wTATc',
            'code_challenge_method' => 'S256',
        ]))->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    }
}
