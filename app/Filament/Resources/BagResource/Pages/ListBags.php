<?php

namespace App\Filament\Resources\BagResource\Pages;

use App\Filament\Resources\BagResource;
use Filament\Resources\Pages\ListRecords;
use function view;

class ListBags extends ListRecords
{
    protected static string $resource = BagResource::class;

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.settings.BagCantCreateNotice');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
