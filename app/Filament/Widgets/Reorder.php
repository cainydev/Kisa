<?php

namespace App\Filament\Widgets;

use App\Filament\Tables\Columns\SparklineColumn;
use App\Jobs\RegenerateHerbStatistics;
use App\Livewire\HerbStatsProgress;
use App\Models\Herb;
use App\Services\Stats\OpenOrderReservations;
use App\Support\Stats\HerbReservation;
use App\Support\Stats\HerbStats;
use App\Support\Stats\HerbStatsRegeneration;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class Reorder extends TableWidget
{
    public int $extrapolateMonths = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Nachbestellung & Lagerprognose')
            ->description('Welche Kräuter bald leer sind, inkl. verkaufter, noch nicht abgefüllter Bestellungen.')
            ->records(fn ($sortColumn, $sortDirection, $search) => $this->records($sortColumn, $sortDirection, $search))
            ->headerActions([
                $this->regenerateStatsAction(),
            ])
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
                    ->state(fn (Herb $record) => $this->depletionDate($record)?->toIso8601String())
                    ->formatStateUsing(function ($state, Herb $record) {
                        if ($this->shortfall($record) > 0) {
                            return 'Reicht nicht';
                        }
                        if (! $state) {
                            return 'Unbekannt';
                        }
                        $date = Carbon::parse($state);

                        if ($date->year > 3000) {
                            return 'Nie';
                        }

                        return $date->isAfter(today()) ? $date->format('d.m.Y') : 'Leer';
                    })
                    ->tooltip(fn (Herb $record): ?string => $this->shortfall($record) > 0
                        ? 'Der Bestand reicht nicht für die offenen Bestellungen.'
                        : null)
                    ->color(fn (Herb $record) => $this->getStockColor($record))
                    ->sortable(),

                SparklineColumn::make('stock_history')
                    ->label('Verlauf (1 Jahr)')
                    ->getStateUsing(fn (Herb $record) => HerbStats::for($record)
                        ->stock()
                        ->lastWeeks(52)
                        ->toChartArray()
                    )
                    ->threshold(fn (Herb $record) => HerbStats::for($record)->averageDailyUsage() * 30),

                TextColumn::make('currentStock')
                    ->label('Bestand')
                    ->size(TextSize::Large)
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->state(fn (Herb $record) => HerbStats::for($record)->currentStock())
                    ->color(fn (Herb $record) => $this->getStockColor($record))
                    ->sortable(),

                TextColumn::make('reserved')
                    ->label('Für Bestellungen')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->placeholder('—')
                    ->color(fn (Herb $record): string => $this->shortfall($record) > 0 ? 'danger' : 'gray')
                    ->state(fn (Herb $record): ?float => $this->reservationFor($record)->isEmpty() ? null : $this->reservationFor($record)->grams)
                    ->description(fn (Herb $record): ?string => $this->shortfall($record) > 0
                        ? $this->formatGrams($this->shortfall($record)).' fehlen'
                        : null)
                    ->tooltip(fn (Herb $record): ?string => $this->reservationTooltip($this->reservationFor($record)))
                    ->sortable(),

                TextColumn::make('averageDailyUsage')
                    ->label('Ø Tag')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->tooltip('Durchschnitt aller verfügbaren Daten')
                    ->state(fn (Herb $record) => HerbStats::for($record)->averageDailyUsage())
                    ->sortable(),

                TextColumn::make('averageWeeklyUsage')
                    ->label('Ø Woche')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->toggleable(true, true)
                    ->state(fn (Herb $record) => HerbStats::for($record)->averageWeeklyUsage())
                    ->sortable(),

                TextColumn::make('averageMonthlyUsage')
                    ->label('Ø Monat')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->toggleable()
                    ->state(fn (Herb $record) => HerbStats::for($record)->averageMonthlyUsage())
                    ->sortable(),

                TextColumn::make('totalUsage')
                    ->label('Gesamt')
                    ->numeric(1, '.', ',')
                    ->suffix(' g')
                    ->toggleable()
                    ->state(fn (Herb $record) => HerbStats::for($record)->totalUsage())
                    ->sortable(),
            ]);
    }

    /**
     * Rebuilds the herb stats on demand. While a run (manual or the nightly
     * one) is in progress the button is swapped for a live progress bar.
     */
    protected function regenerateStatsAction(): Action
    {
        return Action::make('regenerateStats')
            ->label('Neu berechnen')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            // Resolved at render time, not when the table is built: the click
            // that queues the run happens after the table was configured.
            ->defaultView(fn (): string => HerbStatsRegeneration::isRunning()
                ? 'filament.widgets.reorder.stats-progress'
                : Action::BUTTON_VIEW)
            ->action(function (): void {
                if (HerbStatsRegeneration::isRunning()) {
                    return;
                }

                HerbStatsRegeneration::queue();
                RegenerateHerbStatistics::dispatch();
            });
    }

    /**
     * Re-render with the fresh stats once the progress bar reports the run finished.
     */
    #[On(HerbStatsProgress::FINISHED_EVENT)]
    public function refreshAfterStatsRegenerated(): void
    {
        $this->flushCachedTableRecords();
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
                // Herbs short for open orders all deplete "today"; the bigger shortfall comes first.
                'estimatedDepletionDate' => ($this->depletionDate($herb)?->timestamp ?? ($sortDirection === 'asc' ? 9999999999 : 0)) - $this->shortfall($herb),
                'reserved' => $this->reservationFor($herb)->grams,
                'currentStock' => $stats->currentStock(),
                'averageDailyUsage' => $stats->averageDailyUsage(),
                'averageWeeklyUsage' => $stats->averageWeeklyUsage(),
                'averageMonthlyUsage' => $stats->averageMonthlyUsage(),
                'totalUsage' => $stats->totalUsage(),
                'name' => $herb->name,
                default => $this->depletionDate($herb)?->timestamp - $this->shortfall($herb) // Default sort
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

    /**
     * Herbs promised to open orders, loaded once per request and shared by
     * every column and the sort.
     *
     * @var Collection<int, HerbReservation>|null
     */
    protected ?Collection $reservations = null;

    protected function reservationFor(Herb $herb): HerbReservation
    {
        $this->reservations ??= app(OpenOrderReservations::class)->perHerb();

        return $this->reservations->get($herb->id) ?? new HerbReservation;
    }

    protected function depletionDate(Herb $herb): ?CarbonImmutable
    {
        return HerbStats::for($herb)->depletionDateWith($this->reservationFor($herb)->grams);
    }

    /**
     * Grams the open orders need beyond what is in stock; 0 when stock covers them.
     */
    protected function shortfall(Herb $herb): float
    {
        return max(-HerbStats::for($herb)->availableStock($this->reservationFor($herb)->grams), 0);
    }

    /**
     * Same format as the table's numeric gram columns.
     */
    protected function formatGrams(float $grams): string
    {
        return number_format($grams, 1, '.', ',').' g';
    }

    protected function reservationTooltip(HerbReservation $reservation): ?string
    {
        if ($reservation->isEmpty()) {
            return null;
        }

        return collect($reservation->lines)
            ->map(fn (array $line): string => "{$line['variant']}: {$line['missing']} Stk. → ".$this->formatGrams($line['grams']))
            ->prepend('Verkauft, aber noch nicht abgefüllt:')
            ->implode("\n");
    }

    protected function getStockColor(Herb $herb): string
    {
        $currentStock = HerbStats::for($herb)->availableStock($this->reservationFor($herb)->grams);

        if ($currentStock <= 0 && ! $this->reservationFor($herb)->isEmpty()) {
            return 'danger';
        }

        // Calculate Average Daily Usage (using the same logic as your threshold)
        $avgDaily = HerbStats::for($herb)->averageDailyUsage();

        // Handle edge case: If we never use it, it technically lasts forever (Green)
        if ($avgDaily <= 0) {
            return 'success';
        }

        // Calculate how many days of stock we have left
        $daysRemaining = $currentStock / $avgDaily;

        // MATCHING LOGIC:
        // 0 - 7 Days   -> Red (Critical)
        // 7 - 30 Days  -> Orange (Below Threshold)
        // > 30 Days    -> Green (Safe)

        if ($daysRemaining <= 7) {
            return 'danger';
        }
        if ($daysRemaining <= 30) {
            return 'warning';
        }

        return 'success';
    }
}
