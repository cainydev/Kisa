<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\DiscordAlerts\Facades\DiscordAlert;
use Symfony\Component\HttpFoundation\Response;

class ValidateBackupToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->bearerToken();

        $validKey = config('backup.token');

        if (! $validKey || ! hash_equals($validKey, $providedKey ?? '')) {
            if (filled(config('discord-alerts.webhook_urls.default'))) {
                DiscordAlert::message('Someone tried to download a backup but failed authentication.');
            }

            abort(401, 'Unauthorized: Invalid backup access key');
        }

        return $next($request);
    }
}
