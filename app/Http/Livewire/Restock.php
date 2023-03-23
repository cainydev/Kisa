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

    public $sortDir;
    public $sort;

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

        $this->sortDir = 'asc';
        $this->sort = 'daysremaining';
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
        $sorts = [
            'name' => 'herb.name',
            'monthlyuse' => function (Herb $herb) {
                $herb->getRedisAveragePerMonth();
            },
            'yearlyuse' => function (Herb $herb) {
                $herb->getRedisAveragePerYear();
            },
            'grammremaining' => function (Herb $herb) {
                $herb->getRedisGrammRemaining();
            },
            'daysremaining' => function (Herb $herb) {
                $herb->getRedisDaysRemaining();
            }
        ];

        return view('livewire.restock', [
            'batch' => $this->batch_uuid != null ? Bus::findBatch($this->batch_uuid) : false,
            'rows' => Herb::all()->filter(function (Herb $herb) {
                return $herb->getRedisAveragePerDay() > 0;
            })->sortBy(callback: $sorts[$this->sort], descending: $this->sortDir == 'desc')
        ]);
    }
}
