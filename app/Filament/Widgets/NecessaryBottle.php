<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bottles\BottleResource;
use App\Models\Variant;
use App\Services\Production\ProductionPlan;
use App\Services\Production\ProductionPlanner;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

use function auth;

class NecessaryBottle extends Widget implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?array $filters = [];

    public Carbon $defaultMaxDate;

    public int $defaultExtrapolateMonths = 3;

    public int $defaultExtrapolateMaxSize = 200;

    protected string $view = 'filament.widgets.necessary-bottle';

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        $this->defaultMaxDate = Carbon::now()->subDays(7)->startOfDay();

        $this->filters = $this->filters ?? [];
        $this->filters['max_date'] = $this->filters['max_date'] ?? $this->defaultMaxDate->toDateString();
        $this->filters['extrapolate_months'] = $this->filters['extrapolate_months'] ?? $this->defaultExtrapolateMonths;
        $this->filters['extrapolate_max_size'] = $this->filters['extrapolate_max_size'] ?? $this->defaultExtrapolateMaxSize;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Notwendige Abfüllungen')
            ->description('Hier siehst du, welche Abfüllungen notwendig sind, um die aktuellen Bestellungen zu packen. Du kannst die Abfüllung direkt hier erstellen.')
            ->deferFilters(false)
            ->filters([
                Filter::make('max_date')
                    ->schema([
                        Select::make('max_date')
                            ->label('Zeitraum ab')
                            ->native(false)
                            ->options([
                                'yesterday' => 'Gestern',
                                'last3days' => 'Letzte 3 Tage',
                                'lastweek' => 'Letzte Woche',
                                'lastmonth' => 'Letzter Monat',
                            ])
                            ->default('lastweek')
                            ->live(),
                    ]),
                Filter::make('extrapolate_months')
                    ->schema([
                        TextInput::make('extrapolate_months')
                            ->label('Hochrechnung Monate')
                            ->numeric()
                            ->minValue(0)
                            ->default(3)
                            ->live(),
                    ]),
                Filter::make('extrapolate_max_size')
                    ->schema([
                        Select::make('extrapolate_max_size')
                            ->label('Hochrechnen bis Größe')
                            ->options(Variant::pluck('size')->unique()->sort()->mapWithKeys(fn ($size) => [$size => $size.'g']))
                            ->native(false)
                            ->default(200)
                            ->live(),
                    ]),
                Filter::make('round_up_quantity')
                    ->schema([
                        Select::make('round_up_quantity')
                            ->label('Menge aufrunden auf')
                            ->native(false)
                            ->options([
                                'none' => 'Keine Aufrundung',
                                '5' => '5',
                                '10' => '10',
                                '15' => '15',
                            ])
                            ->default('none')
                            ->live(),
                    ]),
            ], FiltersLayout::AboveContent)
            ->filtersFormColumns(['xs' => 1, 'sm' => 2, 'xl' => 4])
            ->records(fn (array $filters): Collection => app(ProductionPlanner::class)->plan($this->planFromFilters($filters)))
            ->columns([
                TextColumn::make('variant_label')
                    ->label('Variante'),
                TextColumn::make('stock')
                    ->icon('billbee')
                    ->iconPosition(IconPosition::After)
                    ->label('Lagerbestand'),
                TextColumn::make('min_needed_quantity')
                    ->label('Benötigte Menge'),
                TextColumn::make('order_references')
                    ->label('Bestellnummern')
                    ->icon('billbee')
                    ->iconPosition(IconPosition::After)
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
            ])
            ->selectable()
            ->headerActions([
                Action::make('createBottleAll')
                    ->label('Abfüllung erstellen')
                    ->action(function () {
                        $records = $this->table->getRecords();
                        if ($records->isEmpty()) {
                            Notification::warning()
                                ->title('Keine Positionen vorhanden.')
                                ->send();
                            throw new Halt;
                        }

                        return $this->createBottleFrom($records);
                    })
                    ->color('gray'),
                BulkAction::make('createBottleSelected')
                    ->label('Abfüllung erstellen mit Auswahl')
                    ->action(function (Collection $records) {
                        if ($records->isEmpty()) {
                            Notification::warning()
                                ->title('Keine Varianten ausgewählt.')
                                ->send();
                            throw new Halt;
                        }

                        return $this->createBottleFrom($records);
                    })
                    ->requiresConfirmation()
                    ->color('primary'),
            ])
            ->emptyStateHeading('Keine anstehenden Abfüllungen notwendig.');
    }

    /**
     * Translate the raw Filament filter state into a typed production plan.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function planFromFilters(array $filters): ProductionPlan
    {
        $since = match ($filters['max_date']['max_date'] ?? 'lastweek') {
            'yesterday' => Carbon::yesterday()->startOfDay(),
            'last3days' => Carbon::now()->subDays(3)->startOfDay(),
            'lastmonth' => Carbon::now()->subMonth()->startOfDay(),
            default => Carbon::now()->subDays(7)->startOfDay(),
        };

        $roundUp = $filters['round_up_quantity']['round_up_quantity'] ?? 'none';

        return new ProductionPlan(
            since: $since,
            extrapolateMonths: filled($filters['extrapolate_months']['extrapolate_months'] ?? null)
                ? (int) $filters['extrapolate_months']['extrapolate_months']
                : $this->defaultExtrapolateMonths,
            extrapolateMaxSize: filled($filters['extrapolate_max_size']['extrapolate_max_size'] ?? null)
                ? (int) $filters['extrapolate_max_size']['extrapolate_max_size']
                : $this->defaultExtrapolateMaxSize,
            roundUpTo: $roundUp === 'none' ? 0 : (int) $roundUp,
        );
    }

    /**
     * Create a bottling from the given planned rows and redirect to it.
     *
     * @param  Collection<int, array<string, mixed>>  $records
     */
    protected function createBottleFrom(Collection $records): mixed
    {
        $bottle = app(ProductionPlanner::class)->createBottle($records, auth()->user());

        return redirect(BottleResource::getUrl('edit', ['record' => $bottle->id]));
    }
}
