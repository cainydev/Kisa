<?php

namespace App\Filament\Resources\Herbs\Pages;

use App\Filament\Resources\Herbs\HerbResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHerb extends CreateRecord
{
    protected static string $resource = HerbResource::class;
}
