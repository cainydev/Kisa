<?php

namespace App\Filament\Widgets;

use App\Models\Herb;
use Carbon\Carbon;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class Reorder extends TableWidget
{
    public int $extrapolateMonths = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Nachbestellung & Lagerprognose')
            ->description('Diese Übersicht zeigt dir, welche Kräuter bald leer sind und nachbestellt werden sollten. Die Prognose basiert auf dem aktuellen Verbrauch und Lagerbestand. Die farblichen Markierungen helfen dir, kritische Bestände schnell zu erkennen.')
            ->records(fn($sortColumn, $sortDirection, $search) => $this->records($sortColumn, $sortDirection, $search))
            ->paginated()
            ->columns([
                TextColumn::make('name')
                    ->label('Kraut')
                    ->searchable(),
                TextColumn::make('estimatedDepletionDate')
                    ->label('Leer ab')
                    ->badge()
                    ->size(TextSize::Large)
                    ->color(fn($state) => !$state ? 'gray' : (Carbon::parse($state)->isPast() ? 'danger' : (Carbon::parse($state)->diffInDays(Carbon::now(), false) <= 30 ? 'warning' : 'success')))
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d.m.Y') : 'Unbekannt')
                    ->sortable(),
                TextColumn::make('currentStock')
                    ->label('Bestand')
                    ->badge()
                    ->size(TextSize::Large)
                    ->color(fn($state) => $state < 50 ? 'danger' : ($state < 200 ? 'warning' : 'success'))
                    ->formatStateUsing(fn($state) => number_format($state, 1) . 'g')
                    ->sortable(),
                TextColumn::make('averageDailyUsage')
                    ->label('Ø Tag')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . 'g')
                    ->sortable(),
                TextColumn::make('averageWeeklyUsage')
                    ->sortable()
                    ->label('Ø Woche')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . 'g')
                    ->toggleable(true, true),
                TextColumn::make('averageMonthlyUsage')
                    ->sortable()
                    ->label('Ø Monat')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . 'g'),
                TextColumn::make('averageYearlyUsage')
                    ->sortable()
                    ->label('Ø Jahr')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . 'g')
                    ->toggleable(),
                TextColumn::make('totalUsage')
                    ->sortable()
                    ->label('Gesamtverbrauch')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . 'g')
                    ->toggleable(),
            ]);
    }

    public function records(?string $sortColumn, ?string $sortDirection, ?string $search): Collection
    {
        $q = Herb::query();

        if (filled($search)) $q->whereLike('name', "%$search%");

        return $q->get()
            ->sortBy($sortColumn ?? 'estimatedDepletionDate', descending: $sortDirection === 'desc');
    }
}
