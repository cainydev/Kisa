<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\{Variant, Bottle, Product, BottlePosition};

class VariantAdder extends Component
{

    public Bottle $bottle;

    public Product $product;
    public $query;

    public Variant $variant;

    public $count = 1;

    protected $rules = [
        'product' => 'required',
        'variant' => 'required',
        'count' => 'numeric|required|max:1000|min:1'
    ];

    public function add()
    {
        $this->validate();


        BottlePosition::create([
            'bottle_id' => $this->bottle->id,
            'variant_id' => $this->variant->id,
            'count' => $this->count
        ]);

        $this->product = new Product;
        $this->variant = new Variant;
        $this->count = 10;

        return redirect(request()->header('Referer'));
    }

    public function delete(BottlePosition $position)
    {
        foreach($position->ingredients as $i){
            $i->delete();
        }

        $position->delete();
    }

    public function mount(Bottle $bottle)
    {
        $this->bottle = $bottle;
    }

    public function setVariant(Variant $variant)
    {
        $this->variant = $variant;

        $bottles = Bottle::where('date', $this->bottle->date)->get();

        foreach ($bottles as $bottle) {
            foreach ($bottle->positions as $pos) {
                if ($pos->variant->id == $this->variant->id) {
                    session()->flash('warning', 'Du hast an diesem Tag bereits eine ähnliche Abfüllung gemacht. Bist du dir sicher?');
                    return;
                }
            }
        }
    }

    public function setProduct(Product $product)
    {
        if ($product->variants->count() < 1) {
            return;
        }

        $this->product = $product;
        $this->setVariant($product->variants->first());
    }

    public function render()
    {
        if (isset($this->product) && $this->product->exists) {
            $this->product = Product::find($this->product->id);
        }

        $this->bottle = Bottle::find($this->bottle->id);
        return view('livewire.variant-adder');
    }
}
