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

use App\Http\Controllers\LabelPreviewController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', Authenticate::class])
    ->group(function () {
        Route::get('/labels/{label}/preview/{page}', LabelPreviewController::class)
            ->name('labels.preview')
            ->where('page', '[a-zA-Z0-9_-]+');
    });
