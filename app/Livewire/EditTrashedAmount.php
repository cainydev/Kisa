<?php

namespace App\Livewire;

use App\Models\Bag;
use Illuminate\View\View;
use Livewire\Component;

class EditTrashedAmount extends Component
{
    public Bag $bag;

    public function mount(Bag|int $bag): void
    {
        $this->bag = $bag instanceof Bag ? $bag : Bag::find($bag);
    }

    public function render(): View
    {
        return view('livewire.edit-trashed-amount');
    }
}
