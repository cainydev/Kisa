<?php

namespace App\Console\Commands;

use App\Models\Variant;
use Illuminate\Console\Command;

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
        foreach (Variant::all() as $variant) {
            if ($variant->getStockFromBillbee()) {
                $this->info($variant->product->mainnumber.$variant->ordernumber.' successfully updated. New Stock: '.$variant->stock);
            } else {
                $this->error($variant->product->mainnumber.$variant->ordernumber.' couldn\'t be updated.');
            }
        }
    }
}
