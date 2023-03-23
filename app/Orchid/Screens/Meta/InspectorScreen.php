<?php

namespace App\Orchid\Screens\Meta;

use App\Models\BioInspector;
use App\Orchid\Layouts\Inspectors\InspectorListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class InspectorScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'inspectors' => BioInspector::paginate(),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Bio-Kontrollstellen';
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
                ->route('platform.meta.inspector.edit'),
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
            Layout::modal('deleteInspector', [
                Layout::view('modals.delete'),
            ])
                ->title('Wirklich löschen?')
                ->applyButton('Löschen')
                ->closeButton('Abbrechen'),
            InspectorListLayout::class,
        ];
    }

    public function deleteInspector($inspector): void
    {
        $inspector = BioInspector::with('suppliers')->find($inspector);
        $inspectorName = $inspector->label;

        $countOfSuppliers = $inspector->suppliers->count();

        if ($countOfSuppliers > 0) {
            Alert::view('toasts.deleteFailed', Color::ERROR(), ['objectName' => 'Kontrollstelle', 'errors' => ['Lieferant' => $countOfSuppliers]]);
        } else {
            $inspector->delete();
            Alert::success('Kontrollstelle '.$inspectorName.' wurde erfolgreich gelöscht.');
        }
    }
}
