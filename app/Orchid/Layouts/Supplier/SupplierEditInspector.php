<?php

namespace App\Orchid\Layouts\Supplier;

use App\Models\BioInspector;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

class SupplierEditInspector extends Rows
{
    protected function fields(): iterable
    {
        return [
            Relation::make('supplier.bio_inspector_id')
                ->fromModel(BioInspector::class, 'label')
                ->title('Bio-Identifikation')
                ->help('Wähle die Bio-Identifikationsnummer')
                ->required(),

        ];
    }
}
