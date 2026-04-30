<?php

namespace App\Providers;

use App\Labels\Hyphenator;
use App\Labels\TemplateRegistry;
use Carbon\Carbon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TemplateRegistry::class, function () {
            return new TemplateRegistry(config('labels.templates', []));
        });

        // Scoped (request-lifetime) so Syllable's compiled pattern dictionary
        // is reused across the request's many param resolutions, but doesn't
        // accumulate across Octane requests.
        $this->app->scoped(Hyphenator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): View => view('components.made-with-love'),
        );

        Carbon::macro('startOfTime', function () {
            return Carbon::createFromFormat('Y-m-d H:i:s', '0001-01-01 00:00:00', 'UTC');
        });

        Carbon::macro('endOfTime', function () {
            return Carbon::createFromFormat('Y-m-d H:i:s', '9999-12-31 23:59:59', 'UTC');
        });
    }
}
