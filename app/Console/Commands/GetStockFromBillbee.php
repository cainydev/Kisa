<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use App\Models\{Product, Variant};

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
        foreach(Variant::all() as $variant){
            if($variant->getStockFromBillbee()){
                $this->info($variant->product->mainnumber . $variant->ordernumber . ' successfully updated. New Stock: ' . $variant->stock);
            }else{
                $this->error($variant->product->mainnumber . $variant->ordernumber . ' couldn\'t be updated.');
            }
        }
    }
}
