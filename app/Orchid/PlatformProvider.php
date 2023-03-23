<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * @return Menu[]
     */
    public function registerMainMenu(): array
    {
        return [
            // Allgemein
            Menu::make('Dashboard')
                ->icon('monitor')
                ->route('platform.info.dashboard')
                ->title('Allgemein'),
            Menu::make('Einstellungen')
                ->icon('settings')
                ->route('platform.info.settings'),

            // Bestand
            Menu::make('Abfüllungen')
                ->icon('filter')
                ->title('Bestand')
                ->route('platform.bottle'),

            Menu::make('Lieferungen')
                ->icon('basket-loaded')
                ->route('platform.deliveries'),

            Menu::make('Säcke')
                ->icon('dropbox')
                ->route('platform.bags'),

            Menu::make('Nachbestellen')
                ->icon('euro')
                ->route('platform.restock'),

            // Produkte
            Menu::make('Rohstoffe')
                ->icon('modules')
                ->title('Produkte')
                ->route('platform.herbs'),

            Menu::make('Endprodukte')
                ->icon('module')
                ->route('platform.products'),

            // Metadaten
            Menu::make('Artikelgruppen')
                ->icon('tag')
                ->title('Metadaten')
                ->route('platform.meta.producttype'),

            Menu::make('Kontrollstellen')
                ->icon('check')
                ->route('platform.meta.inspector'),

            Menu::make('Lieferanten')
                ->icon('basket')
                ->route('platform.meta.supplier'),

            // Zugriffsrechte
            Menu::make(__('Users'))
                ->icon('user')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access rights')),

            Menu::make(__('Roles'))
                ->icon('lock')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
        ];
    }

    /**
     * @return Menu[]
     */
    public function registerProfileMenu(): array
    {
        return [
            Menu::make('Profile')
                ->route('platform.profile')
                ->icon('user'),
        ];
    }

    /**
     * @return ItemPermission[]
     */
    public function registerPermissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),
        ];
    }
}
