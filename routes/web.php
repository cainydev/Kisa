<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

use App\Models\{Bottle, Variant};

Route::get('/', function () {
    return redirect('/a');
});

Route::get('/test', function () {
    return view('partials/boolean', ['value' => true]);
});

require __DIR__ . '/auth.php';
