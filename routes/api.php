<?php

use App\Http\Controllers\BillbeeController;
use App\Http\Middleware\AuthenticateBillbeeRequest;
use App\Http\Middleware\ValidateBackupToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Backup\BackupDestination\BackupDestination;

Route::middleware('auth:web')->get('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
});

Route::middleware(ValidateBackupToken::class)->get('/backup', function (Request $request) {
    $backupName = config('backup.backup.name');
    $disks = config('backup.backup.destination.disks', []);

    $newest = collect($disks)
        ->map(fn (string $disk) => BackupDestination::create($disk, $backupName)->newestBackup())
        ->filter()
        ->sortByDesc(fn ($backup) => $backup->date())
        ->first();

    if ($newest === null || ! $newest->exists()) {
        return response(['message' => 'No backups found.'], 204);
    }

    return $newest->disk()->download($newest->path());
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
