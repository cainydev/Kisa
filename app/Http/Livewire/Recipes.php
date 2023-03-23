<?php

namespace App\Http\Livewire;

use App\Models\{Bottle};
use Livewire\Component;

class Recipes extends Component
{
    public $bottle;

    protected $listeners = ['updateRecipes'];

    public function mount(Bottle $bottle)
    {
        $this->bottle = $bottle;
    }

    public function updateRecipes()
    {
        $this->update = true;
    }

    public function render()
    {
        return view('livewire.recipes');
    }
}
