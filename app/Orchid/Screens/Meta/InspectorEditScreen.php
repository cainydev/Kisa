<?php

namespace App\Orchid\Screens\Meta;

use Illuminate\Http\Request;

use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Actions\{Button, Link};

use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Alert;

use App\Models\BioInspector;

class InspectorEditScreen extends Screen
{
    /**
     * @var BioInspector
     */
    public $inspector;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(BioInspector $inspector): iterable
    {
        return [
            'inspector' => $inspector
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->inspector->exists ? 'Bio-Kontrollstelle bearbeiten' : 'Neue Bio-Kontrollestelle erstellen';
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
                ->route('platform.meta.inspector'),
            Button::make('Speichern')
                ->icon('save')
                ->class('btn btn-success ml-2')
                ->method('createOrUpdate')
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
                Input::make('inspector.company')
                    ->title('Firma')
                    ->placeholder('Kontrollfreak AG')
                    ->help('Die Verantwortlichen der Kontrollstelle')
                    ->required(),
                Input::make('inspector.label')
                    ->title('Bio-Identifikationsnummer')
                    ->placeholder('DE-ÖKO-039')
                    ->required()
            ])
        ];
    }

    /**
     * @param Post    $post
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdate(BioInspector $inspector, Request $request)
    {
        $inspector->fill($request->get('inspector'))->save();

        Alert::success('Kontrollstelle ' . $inspector->label . ' wurde gespeichert.');

        return redirect()->route('platform.meta.inspector');
    }
}
