<?php

namespace App\Orchid\Layouts\Herb;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\{Input, Relation};

use App\Models\Supplier;

class HerbEditLayout extends Rows
{
    protected $title;

    protected function fields(): iterable
    {
        return [
            Input::make('herb.name')
            ->title('Bezeichnung')
            ->required(),
            Input::make('herb.fullname')
            ->title('Vollständige Bezeichnung')
            ->required(),
            Relation::make('herb.supplier_id')
            ->fromModel(Supplier::class, 'shortname')
            ->title('Standardlieferant')
            ->required()
        ];
    }
}
