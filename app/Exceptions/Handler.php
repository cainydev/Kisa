<?php

namespace App\Exceptions;

use Filament\Facades\Filament;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Convert an authentication exception into a response.
     *
     * The app has no route named "login" — the only login screen is the
     * Filament admin panel. Passport's OAuth authorize flow (and any other
     * guarded web route) triggers this when a guest hits it, so redirect to
     * the panel login instead of the framework's default route('login').
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
    }
}
