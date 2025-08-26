<?php

namespace App\Providers;

use Exception;
use App\Settings\BillbeeSettings;
use BillbeeDe\BillbeeAPI\Client;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class BillbeeServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $settings = $app->make(BillbeeSettings::class);

            $user = $settings->username ?? config('services.billbee.username');
            $apiPassword = $settings->password ?? config('services.billbee.api_password');
            $apiKey = $settings->key ?? config('services.billbee.api_key');

            if (empty($user) || empty($apiPassword) || empty($apiKey)) {
                throw new Exception('Billbee API credentials are missing.');
            }

            return new Client($user, $apiPassword, $apiKey);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Client::class];
    }
}
