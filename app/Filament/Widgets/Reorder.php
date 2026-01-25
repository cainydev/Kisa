<?php

namespace App\Filament\Widgets;

use App\Filament\Tables\Columns\SparklineColumn;
use App\Models\Herb;
use App\Support\Stats\HerbStats;
use Carbon\Carbon;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Pagination\LengthAwarePaginator;

class Reorder extends TableWidget
{
    public int $extrapolateMonths = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Nachbestellung & Lagerprognose')
            ->description('Diese Übersicht zeigt dir, welche Kräuter bald leer sind.')
            ->records(fn($sortColumn, $sortDirection, $search) => $this->records($sortColumn, $sortDirection, $search))
            ->columnManager(false)
            ->searchable(false)
            ->paginated([12, 20, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(15)
            ->columns([
                TextColumn::make('name')
                    ->label('Kraut')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('estimatedDepletionDate')
                    ->label('Leer ab')
                    ->badge()
                    ->size(TextSize::Large)
                    ->state(fn(Herb $record) => HerbStats::for($record)->estimatedDepletionDate()?->toIso8601String())
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Unbekannt';
                        $date = Carbon::parse($state);
                        return $date->year > 3000 ? 'Nie' : $date->format('d.m.Y');
                    })
                    ->color(fn(Herb $record) => $this->getStockColor($record))
                    ->sortable(),

                SparklineColumn::make('stock_history')
                    ->label('Verlauf (1 Jahr)')
                    ->getStateUsing(fn(Herb $record) => HerbStats::for($record)
                        ->stock()
                        ->lastWeeks(52)
                        ->toChartArray()
                    )
                    ->threshold(function (Herb $record) {
                        $avgDaily = HerbStats::for($record)->usage()->get()->avg() ?? 0;
                        return $avgDaily * 30;
                    }),

                TextColumn::make('currentStock')
                    ->label('Bestand')
                    ->size(TextSize::Large)
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->state(fn(Herb $record) => HerbStats::for($record)->currentStock())
                    ->color(fn(Herb $record) => $this->getStockColor($record))
                    ->sortable(),

                TextColumn::make('averageDailyUsage')
                    ->label('Ø Tag')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->tooltip('Durchschnitt aller verfügbaren Daten')
                    ->state(fn(Herb $record) => HerbStats::for($record)->usage()->get()->avg() ?? 0)
                    ->sortable(),

                TextColumn::make('averageWeeklyUsage')
                    ->label('Ø Woche')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->toggleable(true, true)
                    ->state(fn(Herb $record) => HerbStats::for($record)->usage()->lastWeeks(52)->get()->avg() ?? 0)
                    ->sortable(),

                TextColumn::make('averageMonthlyUsage')
                    ->label('Ø Monat')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->toggleable()
                    ->state(fn(Herb $record) => HerbStats::for($record)->usage()->lastMonths(12)->get()->avg() ?? 0)
                    ->sortable(),

                TextColumn::make('totalUsage')
                    ->label('Gesamt')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->toggleable()
                    ->state(fn(Herb $record) => HerbStats::for($record)->totalUsage())
                    ->sortable(),
            ]);
    }

    public function records(?string $sortColumn, ?string $sortDirection, ?string $search): LengthAwarePaginator
    {
        $q = Herb::query();

        if (filled($search)) {
            $q->whereLike('name', "%$search%");
        }

        $allRecords = $q->get();

        $sorted = $allRecords->sortBy(function (Herb $herb) use ($sortDirection, $sortColumn) {
            $stats = HerbStats::for($herb);

            return match ($sortColumn) {
                'estimatedDepletionDate' => $stats->estimatedDepletionDate()?->timestamp ?? ($sortDirection === 'asc' ? 9999999999 : 0),
                'currentStock' => $stats->currentStock(),
                'averageDailyUsage' => $stats->usage()->get()->avg(),
                'averageWeeklyUsage' => $stats->usage()->lastWeeks(52)->get()->avg(),
                'averageMonthlyUsage' => $stats->usage()->lastMonths(12)->get()->avg(),
                'totalUsage' => $stats->totalUsage(),
                'name' => $herb->name,
                default => $stats->estimatedDepletionDate()?->timestamp // Default sort
            };
        }, descending: $sortDirection === 'desc');

        $perPage = $this->getTableRecordsPerPage() === 'all' ? $allRecords->count() : $this->getTableRecordsPerPage();
        $perPage = $perPage ?: $this->getDefaultTableRecordsPerPageSelectOption();
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $currentItems = $sorted->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $sorted->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    protected function getStockColor(Herb $herb): string
    {
        $currentStock = HerbStats::for($herb)->currentStock();

        // Calculate Average Daily Usage (using the same logic as your threshold)
        $avgDaily = HerbStats::for($herb)->usage()->get()->avg() ?? 0;

        // Handle edge case: If we never use it, it technically lasts forever (Green)
        if ($avgDaily <= 0) return 'success';

        // Calculate how many days of stock we have left
        $daysRemaining = $currentStock / $avgDaily;

        // MATCHING LOGIC:
        // 0 - 7 Days   -> Red (Critical)
        // 7 - 30 Days  -> Orange (Below Threshold)
        // > 30 Days    -> Green (Safe)

        if ($daysRemaining <= 7) return 'danger';
        if ($daysRemaining <= 30) return 'warning';

        return 'success';
    }
}
