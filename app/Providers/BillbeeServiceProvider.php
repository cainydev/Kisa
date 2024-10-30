<?php

namespace App\Providers;

use BillbeeDe\BillbeeAPI\Client;
use Illuminate\Support\ServiceProvider;

class BillbeeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $user = config('services.billbee.username');
            $apiPassword = config('services.billbee.api_password');
            $apiKey = config('services.billbee.api_key');

            return new Client($user, $apiPassword, $apiKey);
        });
    }
}
