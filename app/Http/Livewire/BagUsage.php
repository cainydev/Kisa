<?php

namespace App\Http\Livewire;

use App\Models\Herb;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;

class BagUsage extends Component
{
    protected $usageChart;

    public $startDate;

    public $endDate;

    public Herb $herb;

    protected $casts = [
        'startDate' => 'date',
        'endDate' => 'date',
    ];

    public function mount()
    {
        $this->herb = Herb::first();
        $this->startDate = Carbon::now()->subYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->generateChart();
    }

    public function generateChart()
    {
        $period = CarbonPeriod::create($this->startDate, $this->endDate);
        $labels = [];

        $period->setRecurrences(10);

        foreach ($period as $date) {
            array_push($labels, $date->toFormattedDateString());
        }

        $this->usageChart =
            app()->chartjs
            ->name('UsageChart')
            ->type('line')
            ->size(['width' => '100%', 'height' => '25px'])
            ->labels($labels)
            ->datasets([
                [
                    'label' => $this->herb->name,
                    'borderColor' => 'green',
                    'data' => [5000, 4400, 4200, 3750, 3000, 2800, 2200, 1200, 1000, 800],
                    'tension' => 0.4,
                ],
            ])
            ->options([]);
    }

    public function render()
    {
        $this->generateChart();

        return view('livewire.bag-usage', ['chart' => $this->usageChart]);
    }
}
