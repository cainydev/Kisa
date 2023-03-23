<?php

use App\Models\Bottle;
use App\Models\BottlePosition;
use App\Models\Herb;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/pos/{pos}/{bag}/{amount}', function ($pos, $bag, $amount, Request $request) {
    return response('Test', 200);

    $pos = BottlePosition::findOrFail($pos);
    $bag = Herb::findOrFail($bag);

    if ($amount != null) {
        Ingredient::create([
            'botte_position_id' => $pos->id,
            'bag_id' => $bag->id,
            'amount' => $amount,
        ]);

        return response('Success!', 200);
    }

    return response('Error, amount not set!', 400);
})->name('addIngredient');

Route::get('/pos/{pos}', function ($pos, Request $request) {
    $position = BottlePosition::findOrFail($pos);

    return response()->json($position->bags->toArray());
});

Route::get('/seed', function () {
    $bottle = Bottle::create([
        '',
    ]);
});
