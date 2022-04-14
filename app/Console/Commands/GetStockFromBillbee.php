<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use App\Models\{Product};

class GetStockFromBillbee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kuw:stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets the current stock values from Billbee';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = env('BILLBEE_USER');
        $pw = env('BILLBEE_PW');
        $key = env('BILLBEE_KEY');
        $host = env('BILLBEE_HOST');

        $page = 0;
        $morePages = true;

        while ($morePages) {
            $page++;
            $products = Http::acceptJson()
                ->withBasicAuth($user, $pw)
                ->withHeaders(['X-Billbee-Api-Key' => $key])
                ->retry(2, 500)
                ->get($host . 'products', [
                    'page' => $page,
                    'pageSize' => 250,
                ])->json();

            $totalPages = $products['Paging']['TotalPages'];
            if ($page >= $totalPages) $morePages = false;

            foreach ($products['Data'] as $entry) {
                $sku = $entry['SKU'];
                $mainnumber = Str::of($sku)->before('.');
                if (Str::of($mainnumber)->length() == 0) continue;
                $ordernumber = Str::of($sku)->after($mainnumber);

                $product = Product::with('variants')->firstWhere('mainnumber', $mainnumber);

                if ($product === null) continue;
                foreach ($product->variants as $variant) {
                    if (Str::of($variant->ordernumber)->is($ordernumber)) {
                        $stock = $entry['StockCurrent'];
                        if ($stock == null) $stock = 0;
                        $variant->stock = $stock;
                    }
                }
            }
        }
    }
}
