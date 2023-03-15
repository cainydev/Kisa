<?php

namespace App\Http\Livewire;

use App\Jobs\AnalyzeHerb;
use App\Models\Herb;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class Restock extends Component
{
    public $batch_uuid;

    public function mount()
    {
        $jobs = Herb::all()->map(fn ($herb) => new AnalyzeHerb($herb))->toArray();

        $batch = Bus::batch($jobs)->dispatch();
        $this->batch_uuid = $batch->id;
    }

    public function render()
    {
        return view('livewire.restock');
    }
}
