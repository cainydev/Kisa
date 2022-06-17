<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\{BottlePosition, Bag, Ingredient};

class Recipe extends Component
{
    public BottlePosition $position;

    public $newCharge;

    protected $rules = [
        'newCharge' => 'required|min:3'
    ];

    public function mount()
    {
        $this->newCharge = $this->position->charge;
    }

    public function refreshHerbs()
    {
        foreach ($this->position->variant->product->herbs as $herb) {
            $this->herbAmounts[$herb->id] = $this->position->getAmount($herb);
        }
    }

    public function setCharge()
    {
        $this->validateOnly('newCharge');
        $this->position->charge = $this->newCharge;
        $this->position->save();
        $this->newCharge = $this->position->charge;
        session()->flash('success', 'Charge wurde manuell angepasst.');
    }
    public function generateCharge()
    {
        $this->position->charge = $this->position->getCharge();
        $this->position->save();
        $this->newCharge = $this->position->charge;
        session()->flash('success', 'Charge wurde automatisch generiert.');
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

    public function removeBag(Bag $bag)
    {
        Ingredient::where('bottle_position_id', $this->position->id)->where('herb_id', $bag->herb->id)->delete();

        session()->flash('warning', $bag->herb->name .  ' ' . $bag->specification . ' wird nicht mehr verwendet.');
    }

    public function render()
    {
        $this->position = BottlePosition::find($this->position->id);
        return view('livewire.recipe');
    }
}
