<?php

namespace App\Filament\Resources\Bags\Pages;

use App\Filament\Resources\Bags\BagResource;
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
