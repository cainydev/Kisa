<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Support\PrintPdf;
use App\Support\Stats\MassBalance;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Mengenflussrechnung / Warenstrombilanz.
 *
 * Per raw material, reconciles Eingang (deliveries) against Verbrauch
 * (fillings) + Ausschuss (verworfen) + Bestand over an optional date window,
 * and flags any herb where more left than came in — the mass-balance check a
 * Bio-Kontrolleur runs. All figures are computed live (set-based queries).
 */
class Mengenfluss extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Mengenfluss';

    protected static ?string $title = 'Mengenflussrechnung';

    protected static string|null|UnitEnum $navigationGroup = NavigationGroup::Overview;

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.mengenfluss';

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    #[Url]
    public bool $onlyIssues = false;

    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    protected ?Collection $rowCache = null;

    /**
     * @var array<string, float|int>|null
     */
    protected ?array $totalsCache = null;

    public function filterAction(): Action
    {
        return Action::make('filter')
            ->label($this->hasRange() ? $this->rangeLabel() : 'Zeitraum wählen')
            ->icon('heroicon-m-calendar-days')
            ->color($this->hasRange() ? 'gray' : 'primary')
            ->fillForm([
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ])
            ->schema([
                DatePicker::make('dateFrom')
                    ->label('Von')
                    ->native(false)
                    ->displayFormat('d.m.Y'),
                DatePicker::make('dateTo')
                    ->label('Bis')
                    ->native(false)
                    ->displayFormat('d.m.Y'),
            ])
            ->modalHeading('Zeitraum eingrenzen')
            ->modalSubmitActionLabel('Anzeigen')
            ->modalWidth('md')
            ->action(function (array $data): void {
                $this->dateFrom = $data['dateFrom'] ?: null;
                $this->dateTo = $data['dateTo'] ?: null;
                $this->flush();
            });
    }

    public function printAction(): Action
    {
        return Action::make('print')
            ->label('Drucken')
            ->icon('heroicon-m-printer')
            ->color('gray')
            ->outlined()
            ->action(function () {
                $pdf = PrintPdf::fromView('print.mengenfluss', [
                    'business' => config('business'),
                    'dateFrom' => $this->dateFrom,
                    'dateTo' => $this->dateTo,
                    'printedAt' => now(),
                    'rows' => $this->rows(),
                    'totals' => $this->totals(),
                ]);

                return response()->streamDownload(
                    fn () => print ($pdf),
                    'mengenfluss-'.now()->format('Ymd-Hi').'.pdf',
                    ['Content-Type' => 'application/pdf'],
                );
            });
    }

    public function clearAction(): Action
    {
        return Action::make('clear')
            ->label('Zurücksetzen')
            ->icon('heroicon-m-x-mark')
            ->color('gray')
            ->link()
            ->visible($this->hasRange())
            ->action(function (): void {
                $this->reset(['dateFrom', 'dateTo']);
                $this->flush();
            });
    }

    public function toggleIssues(): void
    {
        $this->onlyIssues = ! $this->onlyIssues;
    }

    public function hasRange(): bool
    {
        return filled($this->dateFrom) || filled($this->dateTo);
    }

    public function rangeLabel(): string
    {
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom)->format('d.m.Y') : '…';
        $to = $this->dateTo ? Carbon::parse($this->dateTo)->format('d.m.Y') : '…';

        return "{$from} – {$to}";
    }

    protected function balance(): MassBalance
    {
        return MassBalance::between($this->dateFrom, $this->dateTo);
    }

    /**
     * All balance rows for the window (unfiltered by the issues toggle),
     * computed once per request.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function allRows(): Collection
    {
        return $this->rowCache ??= $this->balance()->rows();
    }

    /**
     * Rows for display, honouring the "nur Auffälligkeiten" toggle.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        return $this->onlyIssues
            ? $this->allRows()->where('plausible', false)->values()
            : $this->allRows();
    }

    /**
     * Totals derived from the already-computed rows — no second query pass.
     *
     * @return array<string, float|int>
     */
    public function totals(): array
    {
        return $this->totalsCache ??= (function (): array {
            $rows = $this->allRows();

            return [
                'herbs' => $rows->count(),
                'delivered' => round($rows->sum('delivered'), 1),
                'used' => round($rows->sum('used'), 1),
                'trashed' => round($rows->sum('trashed'), 1),
                'stock' => round($rows->sum('stock'), 1),
                'implausible' => $rows->where('plausible', false)->count(),
            ];
        })();
    }

    protected function flush(): void
    {
        $this->rowCache = null;
        $this->totalsCache = null;
        $this->resetTable();
    }

    /**
     * Format grams as a compact kg string for display.
     */
    public function kg(float|int $grams): string
    {
        return number_format($grams / 1000, 1, ',', '.').' kg';
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage, ?string $sortColumn, ?string $sortDirection, ?string $search): LengthAwarePaginator {
                $rows = $this->rows();

                if (filled($search)) {
                    $needle = Str::lower($search);
                    $rows = $rows->filter(fn (array $r) => str_contains(Str::lower($r['herb']), $needle));
                }

                if ($sortColumn) {
                    $rows = $sortDirection === 'desc'
                        ? $rows->sortByDesc($sortColumn)
                        : $rows->sortBy($sortColumn);
                }

                $rows = $rows->values();

                return new LengthAwarePaginator(
                    $rows->forPage($page, $recordsPerPage)->all(),
                    total: $rows->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->defaultSort('delivered', 'desc')
            ->paginationPageOptions([25, 50, 100])
            ->recordClasses(fn (array $record) => $record['plausible'] ? null : 'fi-mengenfluss-flagged')
            ->columns([
                TextColumn::make('herb')
                    ->label('Rohstoff')
                    ->weight('medium')
                    ->searchable()
                    ->sortable()
                    ->icon(fn (array $record) => $record['plausible'] ? null : 'heroicon-m-exclamation-triangle')
                    ->iconColor('danger'),

                TextColumn::make('delivered')
                    ->label('Eingang')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $this->kg((float) $state))
                    ->color('gray'),

                TextColumn::make('used')
                    ->label('Verbrauch')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $this->kg((float) $state))
                    ->color('gray'),

                TextColumn::make('trashed')
                    ->label('Verlust')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $this->kg((float) $state))
                    ->color('gray'),

                TextColumn::make('stock')
                    ->label('Bestand')
                    ->alignEnd()
                    ->sortable()
                    ->weight('semibold')
                    ->formatStateUsing(fn ($state) => $this->kg((float) $state)),

                TextColumn::make('balance')
                    ->label('Bilanz')
                    ->alignEnd()
                    ->badge()
                    ->sortable()
                    ->state(fn (array $record) => ($record['balance'] >= 0 ? '+' : '−').$this->kg(abs($record['balance'])))
                    ->color(fn (array $record) => $record['plausible'] ? 'success' : 'danger')
                    ->icon(fn (array $record) => $record['plausible'] ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
            ])
            ->recordActions([
                Action::make('trace')
                    ->label('Warenweg')
                    ->icon('heroicon-m-magnifying-glass-circle')
                    ->color('gray')
                    ->url(fn (array $record): string => Warenweg::getUrl([
                        'type' => 'herb',
                        'entityId' => $record['herb_id'],
                        'dateFrom' => $this->dateFrom,
                        'dateTo' => $this->dateTo,
                    ])),
            ])
            ->emptyStateHeading('Keine Bewegungen im gewählten Zeitraum')
            ->emptyStateIcon('heroicon-o-scale');
    }
}
