<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\{Bottle};

class Recipes extends Component
{
    public $bottle;

    public function mount(Bottle $bottle){
        $this->bottle = $bottle;
    }

    public function render()
    {
        return view('livewire.recipes');
    }
}
