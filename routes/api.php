<?php

use App\Http\Controllers\BillbeeController;
use App\Http\Controllers\MediaUploadController;
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

/*
 * Document upload for deliveries (invoice, delivery note, certificate) and
 * certificates. The signature is the credential: it is minted by the
 * request-upload-url MCP tool, scoped to one record and collection, and
 * expires. There is no session here, so scanners and scripts can PUT straight
 * to the URL.
 */
Route::middleware('signed')
    ->post('/uploads/{type}/{id}/{collection}', [MediaUploadController::class, 'store'])
    ->whereNumber('id')
    ->name('media.upload');
