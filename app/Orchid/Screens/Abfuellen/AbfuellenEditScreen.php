<?php

namespace App\Orchid\Screens\Abfuellen;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\{Alert, Layout};
use Orchid\Screen\Actions\{Button, Link};

use App\Orchid\Layouts\VariantListener;
use App\Orchid\Layouts\Abfuellen\{AbfuellenGeneralLayout, AbfuellenPositionsLayout};

use App\Models\{Bottle, BottlePosition, Product, Variant};

class AbfuellenEditScreen extends Screen
{
    /**
     * @var Bottle
     */
    public Bottle $bottle;

    public $variant;

    public $count;

    public function query(Bottle $bottle): iterable
    {
        return [
            'bottle' => $bottle
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Abfüllung ' . ($this->bottle->exists ? 'bearbeiten' : 'erstellen');
    }

    public function description(): ?string
    {
        return 'Du musst die Abfüllung erstmal Speichern, bevor du Produkte hinzufügen kannst!';
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
                ->route('platform.bottle'),
            Button::make('Speichern')
                ->icon('save')
                ->class('btn btn-success')
                ->method('createOrUpdate'),
            Link::make('Rezepte')
                ->icon('book-open')
                ->canSee($this->bottle != null && $this->bottle->exists && $this->bottle->positions->count() > 0)
                ->class('btn btn-link')
                ->route('platform.bottle.recipe', ['bottle' => $this->bottle])
        ];
    }

    public function asyncGetVariants(Product $product = null, Variant $variant = null, $count)
    {

        $variants = $product->variants->mapWithKeys(function ($v, $key) {
            return [$v->id => strval($v->size)];
        })->all();

        return [
            'product' => $product,
            'variants' => $variants ?? [0 => 'Error: Keine Varianten gefunden.'],
            'variant' => $variant != null ? $variant->id : $variants[key($variants)],
            'count' => $count ?? 10,
        ];
    }

    public function addVariant(Bottle $bottle = null, Request $request)
    {

        if ($bottle == null) {
            $bottle = Bottle::create($request->get('bottle'));
        }

        BottlePosition::create([
            'bottle_id' => $bottle->id,
            'variant_id' => $request->get('variant'),
            'count' => $request->get('count')
        ]);

        Alert::success('Produkt wurde hinzugefügt.');
        return redirect(route('platform.bottle.edit', ['bottle' => $bottle]));
    }

    public function deleteVariant(Bottle $bottle, $function, $position)
    {
        BottlePosition::find($position)->delete();
        Alert::success('Produkt wurde von Abfüllung entfernt.');
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        $elems = [AbfuellenGeneralLayout::class];

        if($this->bottle != null && $this->bottle->exists){
            array_push($elems, Layout::livewire('variant-adder'));
        }

        return $elems;
    }

    public function createOrUpdate(Bottle $bottle, Request $request)
    {
        $bottle->fill($request->get('bottle'))->save();

        Alert::success('Abfüllung wurde gespeichert.');

        return redirect()->route('platform.bottle.edit', $bottle);
    }
}
