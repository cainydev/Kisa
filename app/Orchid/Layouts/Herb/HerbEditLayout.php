<?php

namespace App\Orchid\Layouts\Herb;

use App\Models\Supplier;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

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
            ->required(),
        ];
    }
}
