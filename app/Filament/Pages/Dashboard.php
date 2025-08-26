<?php

namespace App\Filament\Pages;

use App\Models\Herb;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

class Dashboard extends \Filament\Pages\Dashboard implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Livewire::make('serializable-closure', [
                'magic' => fn($s) => Herb::whereLike('name', "%$s%")->pluck("name")
            ]),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            //NecessaryBottle::make(),
            //NextBottles::make(),
        ];
    }
}
