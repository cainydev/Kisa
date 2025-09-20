<?php

namespace App\Providers;

use Exception;
use App\Settings\BillbeeSettings;
use Billbee\CustomShopApi\Security\KeyAuthenticator;
use Illuminate\Support\ServiceProvider;

class BillbeeCustomShopKeyAuthenticatorProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(KeyAuthenticator::class, function ($app) {
            $settings = $app->make(BillbeeSettings::class);

            $key = $settings->customShopKey ?? config('billbee-custom-shop.key');

            if (empty($key)) {
                throw new Exception('Billbee Custom Shop Key is missing.');
            }

            return new KeyAuthenticator($key);
        });
    }
}
