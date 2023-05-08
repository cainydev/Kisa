<?php

namespace App\Http\Livewire;

use App\Models\Product;
use App\Models\Variant;
use Livewire\Component;

class VariantMaker extends Component
{
    public Product $product;

    public $size = 100;

    public $sku = '';

    protected $rules = [
        'size' => 'numeric|min:1|max:100000|required',
        'sku' => 'sometimes|starts_with:.',
        'product.variants.*.size' => 'numeric|min:1|max:100000|required',
        'product.variants.*.ordernumber' => 'sometimes|starts_with:.',
    ];

    protected $messages = [
        'sku.starts_with' => 'Die ordernumber muss mit einem Punkt beginnen!',
        'product.variants.*.ordernumber.starts_with' => 'Die ordernumber muss mit einem Punkt beginnen!',
    ];

    public function mount(Product $product)
    {
        $this->product = $product;
    }

    public function saveActiveVariants()
    {
        foreach ($this->product->variants as $variant) {
            if ($variant->ordernumber == null) {
                $variant->ordernumber = '';
            } else {
                $sku = str($variant->ordernumber);
                $variant->ordernumber = $sku->isEmpty() ? $sku : $sku->start('.');
            }
        }

        $this->validateOnly('product.variants.*');
        $this->product->variants->each->save();

        session()->flash('success', 'Varianten wurden gespeichert.');
    }

    public function addNewVariant()
    {
        if ($this->sku == null) {
            $this->sku = '';
        } else {
            $sku = str($this->sku);
            $this->sku = $sku->isEmpty() ? $sku : $sku->start('.');
        }

        $this->validateOnly('size');
        $this->validateOnly('sku');

        Variant::create([
            'size' => $this->size,
            'ordernumber' => $this->sku,
            'product_id' => $this->product->id,
        ]);

        $this->size = 100;
        $this->sku = '';

        $this->product->refresh();

        session()->flash('message', 'Variante wurde erfolgreich erstellt.');
    }

    public function remove(Variant $variant)
    {
        $message = 'Variante konnte nicht entfernt werden:<br/>';
        $canDelete = true;

        foreach ($variant->positions as $pos) {
            $canDelete = false;
            $message .= "Die Variante wird aktuell in der <a class='underline' href='" . route('platform.bottle.edit', $pos->bottle->id) . "'>" . 'Abfüllung [ID ' . $pos->bottle->id . ']</a> verwendet.<br/>';
        }

        if ($canDelete) {
            $variant->delete();
            $this->product->refresh();
            session()->flash('success', 'Variante wurde erfolgreich entfernt.');
        } else {
            session()->flash('error', $message);
        }
    }

    public function render()
    {
        return view('livewire.variant-maker');
    }
}
