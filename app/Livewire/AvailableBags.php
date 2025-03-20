<?php

namespace App\Livewire;

use App\Models\Bag;
use App\Models\Herb;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class AvailableBags extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    /**
     * @var Collection $position
     */
    public Collection $positions;

    /**
     * @var Herb $herb
     */
    public Herb $herb;

    public function mount(Collection|array|null $positions, Herb $herb): void
    {
        $this->positions = collect($positions);
        $this->herb = $herb;
    }

    public function table(Table $table): Table
    {
        return $table->query(Bag::whereHerbId($this->herb->id));
    }

    public function render(): View
    {
        return view('livewire.available-bags');
    }
}
