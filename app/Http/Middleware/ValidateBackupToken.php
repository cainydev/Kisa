<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateBackupToken
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->bearerToken();

        $validKey = config('backup.backup.password');

        if (!$validKey || !hash_equals($validKey, $providedKey ?? '')) {
            abort(401, 'Unauthorized: Invalid backup access key');
        }

        return $next($request);
    }
}
