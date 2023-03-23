<?php

namespace App\Orchid\Screens\Herb;

use App\Models\Herb;
use App\Orchid\Layouts\Herb\HerbEditLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;

class HerbEditScreen extends Screen
{
    public Herb $herb;

    public function query(Herb $herb): iterable
    {
        return [
            'herb' => $herb,
        ];
    }

    public function name(): ?string
    {
        return 'Rohstoff '.($this->herb->exists ? 'bearbeiten' : 'erstellen');
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
                ->route('platform.herbs'),
            Button::make('Speichern')
            ->icon('save')
                ->class('btn btn-success')
                ->method('createOrUpdate'),
        ];
    }

    public function createOrUpdate(Herb $delivery, Request $request)
    {
        $delivery->fill($request->get('herb'))->save();

        Alert::success('Rohstoff wurde gespeichert.');

        return redirect()->route('platform.herbs');
    }

    public function layout(): iterable
    {
        return [
            HerbEditLayout::class,
        ];
    }
}
