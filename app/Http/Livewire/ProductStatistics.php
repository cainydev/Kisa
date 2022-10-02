<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Blade;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Product;

class ProductStatistics extends Component
{
    public Product $product;

    public function mount()
    {
        foreach ($this->product->variants as $v) {
            $v->getStockFromBillbee();
        }
    }

    public function printPDF()
    {
        $name = str('auswertung_' . $this->product->name . '_' . Carbon::now())->slug() . '.pdf';
        $pdf = PDF::loadHTML(Blade::render('<x-product-statistic :product="$product"/>', ['product' => $this->product]));

        Storage::put('stats/' . $name, $pdf->output());

        return Storage::download('stats/' . $name, $name, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' =>  'attachment; filename="' . $name . '"',
            'Content-Length' => strlen($pdf->output()),
        ]);
    }

    public function render()
    {
        $this->product = Product::find($this->product->id);
        return view('livewire.product-statistics');
    }
}
