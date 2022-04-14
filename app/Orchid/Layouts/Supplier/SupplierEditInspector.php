<?php

namespace App\Orchid\Layouts\Supplier;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;

use Orchid\Screen\Fields\Relation;

use App\Models\BioInspector;

class SupplierEditInspector extends Rows
{

    protected function fields(): iterable
    {
        return [
            Relation::make('supplier.bio_inspector_id')
                ->fromModel(BioInspector::class, 'label')
                ->title('Bio-Identifikation')
                ->help('Wähle die Bio-Identifikationsnummer')
                ->required()

        ];
    }
}
