<?php

namespace App\Orchid\Layouts;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\{Button};
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Listener;
use Orchid\Support\Facades\Layout;

class VariantListener extends Listener
{
    protected $asyncMethod = 'asyncGetVariants';

    protected $targets = [
        'product',
        'variant',
        'count',
    ];

    protected function layouts(): iterable
    {
        Log::debug($this->query->get('bottle'));

        return [
            Layout::rows([
                Relation::make('product')
                    ->title('Produkt auswählen')
                    ->fromModel(Product::class, 'name', 'id')
                    ->value($this->query->get('product') ?? 0),

                Select::make('variant')
                    ->title('Variante auswählen')
                    ->canSee($this->query->has('variants'))
                    ->options($this->query->get('variants'))
                    ->value($this->query->get('variant.size')),

                Input::make('count')
                    ->title('Anzahl')
                    ->type('number')
                    ->min(1)
                    ->canSee($this->query->has('variant')),

                Button::make('Hinzufügen')
                    ->method('addVariant')
                    ->class('btn btn-success')
                    ->parameters(['variant' => $this->query->get('variant'), 'count' => $this->query->get('count')])
                    ->canSee($this->query->has('count') && $this->query->get('count') != null),

            ])->title('Produkte hinzufügen die abgefüllt werden'),
        ];
    }
}
