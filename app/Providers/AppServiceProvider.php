<?php

namespace App\Providers;

use App\Labels\Hyphenator;
use App\Labels\TemplateRegistry;
use App\Support\Weight;
use Carbon\Carbon;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Laravel\Passport\Passport;

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
        // Load the Vite-built Warenweg graph component on every panel page as a
        // module, so its Alpine component is registered before any page's x-data
        // is evaluated. This is Filament's documented pattern for Vite assets.
        //
        // Guarded to HTTP context: Vite::asset() reads the build manifest the
        // moment it's called, and boot() also runs for console commands such as
        // package:discover during `composer install` — before assets are built,
        // where no page is ever served. Nothing in console-land renders this
        // asset, so skipping registration there is safe.
        if (! $this->app->runningInConsole()) {
            FilamentAsset::register([
                Js::make('warenweg-graph', Vite::asset('resources/js/warenweg-graph.js'))->module(),
            ]);
        }

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

        Number::macro('kilos', fn (int|float $grams, int $precision = 2): string => Weight::kilos($grams, $precision));
        Number::macro('grams', fn (int|float $grams, int $precision = 0): string => Weight::grams($grams, $precision));

        // The MCP server is protected with OAuth via Passport; this is the
        // consent screen shown to a user when an MCP client (e.g. Claude
        // Desktop) requests access on their behalf.
        Passport::authorizationView(fn ($parameters) => view('mcp.authorize', $parameters));
    }
}
