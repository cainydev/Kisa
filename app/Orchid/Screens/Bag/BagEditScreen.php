<?php

namespace App\Orchid\Screens\Bag;

use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\{Button, Link};
use Orchid\Support\Facades\{Alert, Layout};

use App\Orchid\Layouts\Bag\BagEditLayout;

use App\Models\{Bag};

class BagEditScreen extends Screen
{
    public Bag $bag;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(Bag $bag): iterable
    {
        return [
            'bag' => $bag,
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->bag->herb->name . ' ' . $this->bag->specification . ' bearbeiten';
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
                ->route('platform.bags'),
            Button::make('Speichern')
                ->icon('save')
                ->class('btn btn-success')
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
            Layout::columns([
                BagEditLayout::class,
                Layout::livewire('bag-dispose'),
            ])
        ];
    }

    public function createOrUpdate(Bag $bag, Request $request)
    {
        $bag->fill($request->get('bag'))->save();

        Alert::success('Sack wurde gespeichert.');

        return redirect()->route('platform.bags');
    }
}
