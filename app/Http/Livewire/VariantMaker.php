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
        'sku' => 'sometimes|starts_with:.',
        'product.variants.*.size' => 'numeric|min:1|max:100000|required',
        'product.variants.*.ordernumber' => 'sometimes|starts_with:.'
    ];

    protected $messages = [
        'sku.starts_with' => 'Die ordernumber muss mit einem Punkt beginnen!',
        'product.variants.*.ordernumber.starts_with' => 'Die ordernumber muss mit einem Punkt beginnen!'
    ];

    public function mount(Product $product)
    {
        $this->product = $product;
    }

    public function updated()
    {
        $this->validate();
        $this->product->variants->each->save();

        session()->flash('success', 'Varianten wurden gespeichert.');
    }

    public function add()
    {
        $this->validate();

        Variant::create([
            'size' => $this->size,
            'ordernumber' => $this->sku,
            'product_id' => $this->product->id,
        ]);

        $this->size = 100;
        $this->sku = null;

        session()->flash('message', 'Variante wurde erfolgreich erstellt.');
    }

    public function remove(Variant $variant)
    {
        $message = "Variante konnte nicht entfernt werden:<br/>";
        $canDelete = true;

        foreach ($variant->positions as $pos) {
            $canDelete = false;
            $message .= "Die Variante wird aktuell in der <a class='underline' href='" . route('platform.bottle.edit', $pos->bottle->id) . "'>" . "Abfüllung [ID " . $pos->bottle->id . "]</a> verwendet.<br/>";
        }

        if ($canDelete) {
            $variant->delete();
            session()->flash('success', 'Variante wurde erfolgreich entfernt.');
        } else {
            session()->flash('error', $message);
        }
    }

    public function render()
    {
        if ($this->product->exists) {
            $this->product = Product::find($this->product->id);
        }
        return view('livewire.variant-maker');
    }
}
