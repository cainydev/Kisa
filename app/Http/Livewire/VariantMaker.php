<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\{Product, Variant};

class VariantMaker extends Component
{
    public Product $product;

    public $size = 100;
    public $sku = '';

    protected $rules = [
        'size' => 'numeric|min:1|max:100000|required',
        'sku' => '',
    ];

    public function mount(Product $product) {
        $this->product = $product;
    }

    public function add(){
        $this->validate();

        if(!str($this->sku)->startsWith('.')){
            $this->sku = '.' . $this->sku;
        }

        Variant::create([
            'size' => $this->size,
            'ordernumber' => $this->sku,
            'product_id' => $this->product->id,
        ]);

        $this->size = null;
        $this->sku = null;
    }

    public function remove(Variant $variant){
        $variant->delete();
    }

    public function render()
    {
        if($this->product->exists) {
            $this->product = Product::find($this->product->id);
        }
        return view('livewire.variant-maker');
    }
}
