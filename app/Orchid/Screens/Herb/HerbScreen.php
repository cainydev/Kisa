<?php

namespace App\Orchid\Screens\Herb;

use App\Models\Herb;
use App\Orchid\Layouts\Herb\HerbListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;

class HerbScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'herbs' => Herb::with('standardSupplier')->get(),
        ];
    }

    public function name(): ?string
    {
        return 'Rohstoffe';
    }

    public function description(): ?string
    {
        return 'Rohstoffe sind die Grundlage von jedem Rezept/Endprodukt';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Hinzufügen')
                ->icon('plus')
                ->class('btn btn-success')
                ->route('platform.herbs.edit'),
        ];
    }

    public function layout(): iterable
    {
        return [
            HerbListLayout::class,
        ];
    }

    public function deleteHerb(Herb $herb)
    {
        $canDelete = true;
        $message = 'Rohstoff konnte nicht gelöscht werden: ';
        foreach ($herb->products as $product) {
            $canDelete = false;
            $message .= 'Der Rohstoff wird aktuell noch im Rezept für '.$product->name.' verwendet. ';
        }

        foreach ($herb->bags as $bag) {
            $canDelete = false;
            $message .= 'Der Rohstoff ist aktuell bei Gebinde/Sack ID:'.$bag->id.' als Inhalt hinterlegt.';
        }

        if ($canDelete) {
            $herb->delete();
            Alert::success('Rohstoff wurde gelöscht.');
        } else {
            Alert::error($message);
        }
    }
}
