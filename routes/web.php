<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    //\App\Services\HerbUsageStatistics::generateAll();
    //GenerateHerbUsageStatistics::dispatch();
    //\App\Jobs\GenerateVariantSalesStatistics::dispatch();
    //\App\Services\VariantStatisticsService::generateAll();

    return "Generating...";
});
