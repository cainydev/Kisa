<?php

namespace App\Orchid\Screens\Meta;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\{Button, Link};
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Alert;

use App\Orchid\Layouts\Supplier\{SupplierEditGeneral, SupplierEditContact, SupplierEditInspector};
use App\Models\Supplier;

class SupplierEditScreen extends Screen
{
    /**
     * @var Supplier
     */
    public $supplier;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(Supplier $supplier): iterable
    {
        return ['supplier' => $supplier];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->supplier->exists ? 'Lieferant bearbeiten' : 'Neuer Lieferant';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Abbrechen')
                ->icon('action-undo')
                ->class('btn btn-link')
                ->route('platform.meta.supplier'),
            Link::make('Neue Bio-Kontrollstelle')
                ->icon('plus')
                ->class('btn btn-link')
                ->route('platform.meta.inspector.edit'),
            Button::make('Speichern')
                ->icon('save')
                ->class('btn btn-success')
                ->method('createOrUpdate')
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
            Layout::block(SupplierEditInspector::class)
                ->title(__('Bio-Kontrollstelle'))
                ->description(__('Wähle aus, von welcher Bio-Kontrollstelle dieser Lieferant geprüft ist. Wenn die Kontrollstelle noch nicht vorhanden ist, muss sie erst erstellt werden.')),

            Layout::block(SupplierEditGeneral::class)
                ->title(__('Allgemein'))
                ->description(__('Bitte hier den Firmennamen und einen Kurznamen eingeben.')),

            Layout::block(SupplierEditContact::class)
                ->title(__('Kontakt'))
                ->description(__('Kontaktinformationen des Lieferanten')),
        ];
    }

    public function createOrUpdate(Supplier $supplier, Request $request)
    {
        $supplier->fill($request->get('supplier'))->save();

        Alert::success('Lieferant ' . $supplier->shortname . ' wurde gespeichert.');

        return redirect()->route('platform.meta.supplier');
    }
}
