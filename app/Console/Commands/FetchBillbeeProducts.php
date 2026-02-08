<?php

namespace App\Console\Commands;

use App\Facades\Billbee;
use App\Models\Variant;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use BillbeeDe\BillbeeAPI\Model\Product;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Log;

class FetchBillbeeProducts extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billbee:products {--perpage=100 : Page size when fetching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches products from Billbee and updates local stock/EAN';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Fetching products from Billbee...');

        $page = 1;
        $pageSize = (int)$this->option('perpage');

        try {
            $firstResponse = Billbee::products()->getProducts($page, $pageSize);
            $totalRows = $firstResponse->paging->totalRows ?? 0;

            $bar = $this->output->createProgressBar($totalRows);
            $bar->start();

            do {
                try {
                    $response = ($page === 1 && isset($firstResponse))
                        ? $firstResponse
                        : Billbee::products()->getProducts($page, $pageSize);

                    foreach ($response->data as $billbeeProduct) {
                        $this->syncProduct($billbeeProduct);
                        $bar->advance();
                    }

                    $totalPages = $response->paging->totalPages ?? 1;
                    $page = ($page < $totalPages) ? $page + 1 : false;

                    unset($response);

                } catch (QuotaExceededException $e) {
                    $this->warn("\nAPI quota exceeded. Pausing...");
                    sleep(2);
                    continue;
                } catch (Exception $e) {
                    $this->error("\nError on page $page: " . $e->getMessage());
                    Log::error($e);
                    return self::FAILURE;
                }

            } while ($page !== false);

            $bar->finish();
            $this->newLine(2);
            $this->info('Done.');

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Critical error: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Updates a single variant based on SKU match.
     */
    private function syncProduct(Product $product): void
    {
        if (empty($product->sku)) {
            return;
        }

        Variant::where('sku', $product->sku)->update([
            'billbee_id' => $product->id,
            'ean' => $product->ean,
            'stock' => $product->stockCurrent ?? 0,
        ]);
    }
}
