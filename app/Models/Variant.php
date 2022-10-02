<?php

namespace App\Models;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Http;

use Orchid\Screen\AsSource;

class Variant extends Model
{
    use HasFactory, AsSource;

    protected $guarded = [];

    protected $with = ['product'];

    public function getSKU()
    {
        return $this->product->mainnumber . $this->ordernumber;
    }

    public function getStockFromBillbee()
    {
        $user = env('BILLBEE_USER');
        $pw = env('BILLBEE_PW');
        $key = env('BILLBEE_KEY');
        $host = env('BILLBEE_HOST');

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($user, $pw)
                ->withHeaders(['X-Billbee-Api-Key' => $key])
                ->retry(2, 500, function ($ex) {
                })
                ->get($host . 'products/' . $this->product->mainnumber . $this->ordernumber, [
                    'lookupBy' => 'sku'
                ]);
        } catch (Exception $e) {
            return false;
        }

        if ($response->failed()) {
            $response = $response->json();
            dd('Couldn\'t get stock from Billbee: ' . $response['ErrorMessage'] . ': ' . $response['ErrorDescription']);
            return false;
        }

        $response = $response->json();

        $this->stock = intval($response['Data']['StockCurrent']);
        $this->save();
        return true;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bottles()
    {
        return $this->belongsToMany(Bottle::class);
    }

    public function positions()
    {
        return $this->hasMany(BottlePosition::class);
    }
}
