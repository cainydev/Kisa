<?php

use App\Http\Controllers\BillbeeController;
use App\Http\Middleware\AuthenticateBillbeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(AuthenticateBillbeeRequest::class)
    ->controller(BillbeeController::class)
    ->group(function () {
        Route::get('/billbee', 'get');
        Route::post('/billbee', 'post');
    });
