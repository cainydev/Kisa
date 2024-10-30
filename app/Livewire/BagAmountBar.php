<?php

namespace App\Livewire;

use App\Models\Bag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class BagAmountBar extends Component
{
    #[Locked]
    public ?Bag $bag;

    public function mount(?Bag $record = null): void
    {
        $this->bag = $record;
    }

    #[On('bag-updated')]
    public function refresh(): void
    {
        $this->bag->refresh();
    }

    public function render(): ?View
    {
        if($this->bag !== null)
            return view('livewire.bag-amount-bar');

        return null;
    }
}
