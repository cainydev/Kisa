<?php

namespace App\Orchid\Screens\Herb;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Alert;

use App\Orchid\Layouts\Herb\HerbListLayout;
use App\Models\Herb;
use PhpParser\Node\Expr\Cast\String_;

class HerbScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'herbs' => Herb::paginate(20)
        ];
    }

    public function name(): ?string
    {
        return 'Rohstoffe';
    }

    public function description(): ? String
    {
        return 'Rohstoffe sind die Grundlage von jedem Rezept/Endprodukt';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Hinzufügen')
                ->icon('plus')
                ->class('btn btn-success')
                ->route('platform.herbs.edit')
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
        $message = "Rohstoff konnte nicht gelöscht werden: ";
        foreach ($herb->products as $product) {
            $canDelete = false;
            $message .= "Der Rohstoff wird aktuell noch im Rezept für " . $product->name . " verwendet. ";
        }

        foreach ($herb->bags as $bag){
            $canDelete = false;
            $message .= "Der Rohstoff ist aktuell bei Gebinde/Sack ID:" . $bag->id . " als Inhalt hinterlegt.";
        }

        if ($canDelete) {
            $herb->delete();
            Alert::success('Rohstoff wurde gelöscht.');
        } else {
            Alert::error($message);
        }
    }
}
