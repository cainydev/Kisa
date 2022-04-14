<?php

namespace App\Orchid\Screens\Meta;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\{Alert, Toast};
use Orchid\Support\Color;


use App\Models\{Supplier};
use App\Orchid\Layouts\Supplier\SupplierListLayout;

class SupplierScreen extends Screen
{

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'suppliers' => Supplier::paginate(),
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Lieferanten';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return "Alle verfügbaren Lieferanten";
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
                ->route('platform.meta.supplier.edit'),
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
            Layout::modal('deleteSupplier', [
                Layout::view('modals.delete')
            ])
                ->title('Wirklich löschen?')
                ->applyButton('Löschen')
                ->closeButton('Abbrechen'),

            SupplierListLayout::class,
        ];
    }

    public function deleteSupplier($supplier): void
    {
        $supplier = Supplier::with(['herbs', 'deliveries'])->find($supplier);
        $supplierName = $supplier->shortname;

        if ($supplier->herbs->count() > 0 || $supplier->deliveries->count() > 0) {
            $errors = [];
            if ($supplier->herbs->count() > 0)
                $errors['Rohstoffe'] = $supplier->herbs->count();
            if ($supplier->deliveries->count() > 0)
                $errors['Lieferungen'] = $supplier->deliveries->count();

            Alert::view('toasts.deleteFailed', Color::DANGER(), ['objectName' => 'Lieferant', 'errors' => $errors]);
        } else {
            $supplier->delete();
            Alert::success('Lieferant ' . $supplierName . ' wurde erfolgreich gelöscht.');
        }
    }
}
