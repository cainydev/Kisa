<?php

namespace App\Orchid\Screens\Delivery;

use App\Models\Bag;
use App\Models\Delivery;
use App\Orchid\Layouts\Delivery\DeliveryBagsLayout;
use App\Orchid\Layouts\Delivery\DeliveryBioLayout;
use App\Orchid\Layouts\Delivery\DeliveryEditLayout;
use App\Orchid\Layouts\Delivery\DeliveryListBagLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class DeliveryEditScreen extends Screen
{
    public Delivery $delivery;

    public Bag $currentBag;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(Delivery $delivery, Bag $currentBag = null): iterable
    {
        return [
            'delivery' => $delivery,
            'currentBag' => $currentBag,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Lieferung '.($this->delivery->exists ? 'bearbeiten' : 'erstellen');
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Zurück')
                ->icon('action-undo')
                ->class('btn btn-link')
                ->route('platform.deliveries'),
            Button::make('Speichern')
                ->icon('save')
                ->class('btn btn-success')
                ->method('createOrUpdate'),
        ];
    }

    public function addBag(Delivery $delivery, Request $request)
    {
        $delivery->fill($request->get('delivery'))->save();

        if (
            $request->has('currentBag.charge') &&
            $request->has('currentBag.bio') &&
            $request->has('currentBag.size') &&
            $request->has('currentBag.herb_id') &&
            $request->has('currentBag.bestbefore')
        ) {
            $bag = (new Bag())->fill(($request->get('currentBag')));
            $bag->delivery_id = $delivery->id;
            $bag->save();

            return redirect()->route('platform.deliveries.edit', ['delivery' => $delivery]);
        } else {
            Alert::error('Bitte fülle alle Felder aus um einen neuen Sack hinzuzufügen.');
        }
    }

    public function deleteBag(Request $request)
    {
        $bag = Bag::find($request->query->get('bag'));

        $message = 'Gebinde konnte nicht gelöscht werden: ';

        $valid = true;
        foreach ($bag->ingredients as $i) {
            $message .= 'Gebinde wird aktuell in Abfüllung (ID='.$i->position->bottle->id.') verwendet.';
            $valid = false;
        }

        if ($valid) {
            $bag->delete();
            Alert::success('Gebinde wurde entfernt.');
        } else {
            Alert::error($message);
        }
    }

    public function createOrUpdate(Delivery $delivery, Request $request)
    {
        $delivery->fill($request->get('delivery'))->save();

        Alert::success('Lieferung wurde gespeichert.');

        return redirect()->route('platform.deliveries.edit', $delivery);
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Allgemein' => DeliveryEditLayout::class,
                'Dokumente' => Layout::view('partials.delivery-documents'),
                'Eingangskontrolle' => DeliveryBioLayout::class,
                'Gebinde' => [
                    DeliveryBagsLayout::class,
                    DeliveryListBagLayout::class,
                ],
            ])->activeTab($this->delivery->exists ? 'Gebinde' : 'Allgemein'),
        ];
    }
}
