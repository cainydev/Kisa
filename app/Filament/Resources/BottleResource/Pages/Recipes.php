<?php

namespace App\Filament\Resources\BottleResource\Pages;

use App\Filament\Resources\BottleResource;
use App\Models\Bottle;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use function url;

class Recipes extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BottleResource::class;

    protected static string $view = 'filament.resources.bottle-resource.pages.recipes';

    public Bottle $bottle;

    public bool $grouped = true;

    /**
     * We need to mount the record to the page for it to be available.
     *
     * @param int|string $record
     * @return void
     */
    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->bottle = Bottle::find($record);
    }

    /**
     * Change the page title.
     *
     * @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return "Rezepte {$this->record->getAttribute('date')->format('d.m.Y')}";
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Zurück')
                ->color('gray')
                ->url(url()->previous()),
            Action::make('group')
                ->color(fn() => $this->grouped ? 'info' : 'gray')
                ->label(fn() => $this->grouped ? 'Gruppiert' : 'Nicht gruppiert')
                ->icon(fn() => $this->grouped ? 'heroicon-s-link' : 'heroicon-s-link-slash')
                ->action(fn() => $this->grouped = !$this->grouped),
        ];
    }
}
