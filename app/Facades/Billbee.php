<?php

namespace App\Facades;

use BillbeeDe\BillbeeAPI\Client;
use Illuminate\Support\Facades\Facade;

class Billbee extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
