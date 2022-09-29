<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Blade;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\{Herb, Bag};

class HerbStatistics extends Component
{
    public Herb $herb;

    public Bag $bag;

    public function mount(){
    }

    public function generateFor(Bag $bag){
        $this->bag = $bag;
    }

    public function printPDF(Bag $bag)
    {
        $name = str('auswertung ' . $bag->herb->name . ' charge ' . $bag->charge . ' ' . Carbon::now())->slug() . '.pdf';
        $pdf = PDF::loadHTML(Blade::render('<x-herb-statistic :bag="$bag"/>', ['bag' => $bag]));

        Storage::put('stats/'. $name, $pdf->output());

        return Storage::download('stats/' . $name, $name, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' =>  'attachment; filename="' . $name . '"',
            'Content-Length' => strlen($pdf->output()),
        ]);
    }

    public function render()
    {
        $this->herb = Herb::find($this->herb->id);
        return view('livewire.herb-statistics');
    }
}
