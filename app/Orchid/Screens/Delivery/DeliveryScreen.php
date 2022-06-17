<?php

namespace App\Orchid\Screens\Delivery;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;

use App\Orchid\Layouts\Delivery\DeliveryListLayout;

use App\Models\Delivery;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class DeliveryScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'deliveries' => Delivery::all()
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Lieferungen';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Hinzufügen')
                ->icon('plus')
                ->class('btn btn-success')
                ->route('platform.deliveries.edit')
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            DeliveryListLayout::class
        ];
    }

    public function deleteDelivery(Delivery $delivery){
        $canDelete = true;
        $message = "";
        foreach($delivery->bags as $bag){
            if($bag->ingredients->count() > 0){
                $canDelete = false;
                $message = "Lieferung konnte nicht gelöscht werden: ";
                foreach($bag->ingredients as $i){
                    $message .= "Das Gebinde " . $bag->herb->name . " " . $bag->getSizeInKilo() . " wurde in Abfüllung ID:" . $i->position->bottle->id . " verwendet. ";
                }
            }
        }

        if($canDelete){
            $delivery->delete();
            Alert::success('Lieferung wurde gelöscht.');
        }else{
            Alert::error($message);
        }

    }
}
