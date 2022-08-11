<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bag;
use Carbon\Carbon;

class ChartsController extends Controller
{
    public static function bagBestBefore()
    {
        $bad = 0;
        $soon = 0;
        $good = 0;

        foreach (Bag::all() as $bag) {
            if ($bag->bestbefore < Carbon::now()) {
                $bad++;
                continue;
            }
            if ($bag->bestbefore < Carbon::now()->addMonths(3)) {
                $soon++;
                continue;
            }

            $good++;
        }


        $chart = app()->chartjs
            ->name('GebindeHaltbarkeitsListe')
            ->type('pie')
            ->size(['width' => 200, 'height' => 200])
            ->labels(['Abgelaufen', 'Läuft bald ab', 'Lange haltbar'])
            ->datasets([
                [
                    'backgroundColor' => ['red', 'yellow', 'green'],
                    'hoverBackgroundColor' => ['red', 'yellow', 'green'],
                    'data' => [$bad, $soon, $good]
                ]
            ])
            ->options([]);

        return $chart;
    }

    public static function bagIsBio()
    {
        $bio = Bag::where('bio', true)->get()->count();
        $nichtBio = Bag::where('bio', false)->get()->count();

        $chart = app()->chartjs
            ->name('GebindeBio')
            ->type('bar')
            ->size(['width' => 200, 'height' => 200])
            ->labels(['Gebinde'])
            ->datasets([
                [
                    'label' => 'Bio',
                    'backgroundColor' => ['green'],
                    'data' => [$bio]
                ],
                [
                    'label' => 'Nicht Bio',
                    'backgroundColor' => ['red'],
                    'data' => [$nichtBio]
                ]
            ])
            ->options([]);

        return $chart;
    }

    public static function bagIsSoonSpoiled()
    {
        return
            Bag::where('bestbefore', '>', Carbon::now())
            ->where('bestbefore', '<', Carbon::now()->addMonths(3))
            ->take(5)
            ->get();
    }
}
