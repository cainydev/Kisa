<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\{Product, Herb};

class RecipeMaker extends Component
{
    public Product $product;
    public Herb $herb;
    public $query = '';
    public $amount = 0;

    protected $rules = [
        'herb' => 'required',
        'amount' => 'numeric|required|max:100|min:0'
    ];

    public function setHerb(Herb $herb){
        $this->herb = $herb;
    }

    public function add(){
        $this->validate();


        $this->product->herbs()->attach($this->herb, ['percentage' => $this->amount]);

        $this->herb = new Herb();
        $this->amount = 0;
    }

    public function mount(Product $product){
        $this->product = $product;
    }

    public function detach(Herb $herb){
        $this->product->herbs()->detach($herb);
    }

    public function render()
    {
        if ($this->product->exists) {
            $this->product = Product::find($this->product->id);
        }
        return view('livewire.recipe-maker');
    }
}
