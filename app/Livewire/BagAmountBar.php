<?php

namespace App\Livewire;

use App\Models\Bag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class BagAmountBar extends Component
{
    public Bag $bag;

    public float $total;
    public float $free;
    public float $used;
    public float $trashed;

    public function mount(Bag $bag): void
    {
        $this->bag = $bag;
        $this->recalculate();
    }

    public function recalculate(): void
    {
        $this->total = $this->bag->size;
        $this->free = $this->bag->getCurrentWithTrashed();
        $this->trashed = $this->bag->trashed;
        $this->used = $this->bag->size - $this->free - $this->trashed;
    }

    #[On('bag.{bag.id}.updated')]
    public function refresh(): void
    {
        $this->js('console.log("refreshed")');
        $this->bag->refresh();
        $this->recalculate();
    }

    public function render(): ?View
    {
        return view('livewire.bag-amount-bar');
    }
}
