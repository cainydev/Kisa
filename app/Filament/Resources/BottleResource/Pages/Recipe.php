<?php

namespace App\Filament\Resources\BottleResource\Pages;

use Illuminate\Support\Collection;
use Livewire\Component;

class Recipe extends Component
{
    use \Filament\Tables\Concerns\InteractsWithTable;

    public Collection $positions;

    /**
     * We need to mount the record to the page for it to be available.
     *
     * @return void
     */
    public function mount(Collection $positions): void
    {
        $this->positions = $positions;
    }
}
