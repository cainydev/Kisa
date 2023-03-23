<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Information;

use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class WelcomeScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Willkommen bei Kräuter & Wege';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Dies ist die interne Software von K&W um Lieferungen, Abfüllungen und Bio-Inspektionen zu kontrollieren.';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Shopware')
                ->href('https://kraeuter-wege.de')
                ->icon('globe-alt'),

            Link::make('Shopware Backend')
                ->href('https://kraeuter-wege.de/backend')
                ->icon('wrench'),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::view('partials.welcome'),
        ];
    }
}
