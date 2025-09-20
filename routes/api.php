<?php

use App\Http\Controllers\BillbeeController;
use App\Http\Middleware\AuthenticateBillbeeRequest;
use App\Http\Middleware\ValidateBackupToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:web')->get('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
    return ['token' => $token->plainTextToken];
});

Route::middleware(ValidateBackupToken::class)->get('/backup', function (Request $request) {
    $files = collect(Storage::files(config('backup.backup.name')))
        ->where(fn($s) => str($s)->endsWith('zip'))
        ->sortDesc();

    if ($files->isEmpty()) return response(['message' => 'No backups found.'], 204);

    return Storage::download($files->first());
})->name('backup');

Route::middleware(AuthenticateBillbeeRequest::class)
    ->controller(BillbeeController::class)
    ->group(function () {
        Route::get('/billbee', 'get');
        Route::post('/billbee', 'post');
    });

Route::get('/{model}/autocomplete/{query}', function (string $model, string $query) {
    return ['model' => $model, 'query' => $query];
})->middleware('auth:sanctum');
