<?php

namespace App\Console\Commands;

use App\Facades\Billbee;
use App\Models\Variant;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use BillbeeDe\BillbeeAPI\Model\Product;
use Exception;
use Illuminate\Console\Command;
use function intval;

class FetchBillbeeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billbee:products {--perpage=250 : Page size when fetching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches all products at once and updates the database';

    /**
     * Execute the console command.
     * @throws QuotaExceededException
     */
    public function handle(): void
    {
        $this->info('Fetching all products from Billbee...');
        $this->newLine();

        $products = collect();
        $page = 1;
        $pageSize = intval($this->argument('perpage'));
        $pagingInfo = Billbee::products()->getProducts($page, $pageSize)->paging;

        $bar = $this->output->createProgressBar($pagingInfo['TotalRows']);
        $bar->start();

        while ($page) {
            try {
                $response = Billbee::products()->getProducts($page, $pageSize);
                $products->push(...$response->data);
                $page = $response->paging['TotalPages'] == $page ? false : $page + 1;
                $bar->advance(count($response->data));
            } catch (QuotaExceededException $e) {
                $this->warn('Billbee API quota exceeded. Let\'s wait a second.');
                sleep(1);
                continue;
            } catch (Exception $e) {
                $this->error($e->getMessage());
                continue;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Updating products...');
        $this->newLine();
        $bar = $this->output->createProgressBar(Variant::count());
        $bar->start();

        foreach (Variant::all() as $variant) {
            $billbeeData = $products->first(function (Product $p) use ($variant) {
                return $p->sku === $variant->sku;
            });

            if ($billbeeData instanceof Product) {
                $variant->billbee_id = $billbeeData->id;
                $variant->ean = $billbeeData->ean;
                $variant->stock = $billbeeData->stockCurrent;
                $variant->save();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Done.');
    }
}
