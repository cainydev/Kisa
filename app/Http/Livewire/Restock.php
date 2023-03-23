<?php

namespace App\Http\Livewire;

use App\Jobs\AnalyzeHerb;
use App\Models\Herb;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class Restock extends Component
{
    public $trashGate;
    public $startDate;
    public $endDate;
    public $batch_uuid;

    protected $rules = [
        'trashGate' => 'numeric|max:100|min:0',
        'startDate' => 'date|nullable|lte:endDate',
        'endDate' => 'date|nullable|gte:startDate',
    ];

    public function mount()
    {
        $this->trashGate = 15;
        $this->startDate = null;
        $this->endDate = null;
    }

    public function generate()
    {
        $this->validate();

        $jobs = Herb::all()->map(
            fn ($herb) =>
            new AnalyzeHerb($herb, $this->trashGate, $this->startDate, $this->endDate)
        )->toArray();

        $batch = Bus::batch($jobs)->dispatch();
        $this->batch_uuid = $batch->id;
    }

    public function abort()
    {
        if ($this->batch_uuid != null && !Bus::findBatch($this->batch_uuid)->finished()) {
            Bus::findBatch($this->batch_uuid)->cancel();
        }
    }

    public function render()
    {
        return view('livewire.restock', ['batch' => $this->batch_uuid != null ? Bus::findBatch($this->batch_uuid) : false]);
    }
}
