<?php

use Filament\Facades\Filament;
use Laravel\Passport\ClientRepository;

/**
 * The MCP server is protected with OAuth 2.1 via Passport. These tests assert
 * the auth boundary: discovery is public, the server rejects unauthenticated
 * calls, and the OAuth authorization endpoint exists.
 */
describe('discovery documents', function () {
    it('serves the protected resource document publicly', function () {
        $this->getJson('/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJsonStructure(['resource', 'authorization_servers', 'scopes_supported'])
            ->assertJsonFragment(['scopes_supported' => ['mcp:use']]);
    });

    it('serves the authorization server document publicly', function () {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint']);
    });
});

describe('the auth boundary', function () {
    it('rejects unauthenticated calls to the mcp server', function () {
        $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ])->assertUnauthorized();
    });

    it('redirects guests from the authorize endpoint to the panel login', function () {
        $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
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
    });
});
