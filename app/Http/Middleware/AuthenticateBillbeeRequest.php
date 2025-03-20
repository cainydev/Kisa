<?php

namespace App\Http\Middleware;

use Billbee\CustomShopApi\Security\KeyAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBillbeeRequest
{
    private KeyAuthenticator $authenticator;

    public function __construct(KeyAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($factory, $factory, $factory, $factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        if ($this->authenticator->isAuthorized($psrRequest)) {
            return $next($request);
        } else {
            return response('Unauthorized', 401);
        }
    }
}
