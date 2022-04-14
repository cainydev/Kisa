<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\{BottlePosition, Bag, Ingredient};

class Recipe extends Component
{
    public BottlePosition $position;
    //public $amounts = [];
    //public $herbAmounts = [];

    public function mount(BottlePosition $position)
    {
        /*$this->position = $position;
        foreach ($this->position->ingredients as $i) {
            $this->amounts[$i->bag->id] = $i->amount;
        }

        $this->refreshHerbs();*/
    }

    public function refreshHerbs()
    {
        foreach ($this->position->variant->product->herbs as $herb) {
            $this->herbAmounts[$herb->id] = $this->position->getAmount($herb);
        }
    }

    public function setBag(Bag $bag)
    {
        Ingredient::updateOrCreate(
            [
                'bottle_position_id' => $this->position->id,
                'herb_id' => $bag->herb->id,
            ],
            [
                'bag_id' => $bag->id,
            ]
        );
        session()->flash('success', $bag->herb->name .  ' ' . $bag->specification . ' wird jetzt verwendet.');
    }

    public function render()
    {
        $this->position = BottlePosition::find($this->position->id);
        return view('livewire.recipe');
    }
}
