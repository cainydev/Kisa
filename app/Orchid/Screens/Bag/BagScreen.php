<?php

namespace App\Orchid\Screens\Bag;

use Orchid\Screen\Screen;

use App\Orchid\Layouts\Bag\BagListLayout;
use App\Models\Bag;
use Orchid\Support\Facades\Alert;

class BagScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'bags' => Bag::filters()->defaultSort('id')->paginate(config('kis.paginate'))
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Säcke';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            BagListLayout::class
        ];
    }

    public function deleteBag(Bag $bag)
    {
        $canDelete = true;
        $message = "";
        foreach ($bag->ingredients as $i) {
            $canDelete = false;
            $message = "Sack konnte nicht gelöscht werden: ";
            foreach ($bag->ingredients as $i) {
                $message .= "Das Gebinde " . $bag->herb->name . " " . $bag->getSizeInKilo() . " wurde in Abfüllung ID:" . $i->position->bottle->id . " verwendet. ";
            }
        }

        if ($canDelete) {
            $bag->delete();
            Alert::success('Sack wurde gelöscht.');
        } else {
            Alert::error($message);
        }
    }
}
