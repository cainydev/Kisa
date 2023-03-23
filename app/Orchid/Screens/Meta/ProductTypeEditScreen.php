<?php

namespace App\Orchid\Screens\Meta;

use App\Models\ProductType;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class ProductTypeEditScreen extends Screen
{
    /**
     * @var ProductType
     */
    public $type;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(ProductType $type): iterable
    {
        return [
            'type' => $type,
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return $this->type->exists ? 'Produktgruppe bearbeiten' : 'Neue Produktgruppe erstellen';
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
                ->class('btn btn-danger')
                ->route('platform.meta.producttype'),
            Button::make('Speichern')
                ->icon('save')
                ->class('btn btn-success ml-2')
                ->method('createOrUpdate'),
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
            Layout::rows([
                Input::make('type.name')
                    ->title('Name der Gruppe')
                    ->required(),
                CheckBox::make('type.compound')
                    ->title('Ist Verbundmischung?')
                    ->sendTrueOrFalse(),
            ])->title('Allgemein'),
        ];
    }

    public function createOrUpdate(ProductType $type, Request $request)
    {
        $type->fill($request->get('type'))->save();

        Alert::success('Produktgruppe '.$type->name.' wurde gespeichert.');

        return redirect()->route('platform.meta.producttype');
    }
}
