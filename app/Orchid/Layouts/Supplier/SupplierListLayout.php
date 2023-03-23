<?php

namespace App\Orchid\Layouts\Supplier;

use App\Models\BioInspector;
use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class SupplierListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'suppliers';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID'),
            TD::make('company', 'Firma'),
            TD::make('shortname', 'Name'),
            TD::make('contact', 'Kontakt'),
            TD::make('email', 'Email')
                ->render(function ($supplier) {
                    return Link::make($supplier->email)
                        ->href('mailto:'.$supplier->email);
                }),
            TD::make('phone', 'Telefon'),
            TD::make('website', 'Webseite')
                ->render(function ($supplier) {
                    return Link::make($supplier->website)
                        ->href('https://'.$supplier->website);
                }),
            TD::make('bio_inspector_id', 'Kontrollstelle')
                ->render(function ($supplier) {
                    $inspector = BioInspector::firstWhere('id', $supplier->bio_inspector_id);
                    if (isset($inspector)) {
                        return Link::make($inspector->label)->route('platform.meta.inspector');
                    } else {
                        return 'Keine Zuordnung';
                    }
                }),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($supplier) {
                    return Group::make([
                        ModalToggle::make()
                            ->modal('deleteSupplier')
                            ->class('btn btn-danger p-2')
                            ->method('deleteSupplier')
                            ->parameters(['supplier-id' => $supplier->id])
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.meta.supplier.edit', $supplier),
                    ]);
                }),
        ];
    }
}
