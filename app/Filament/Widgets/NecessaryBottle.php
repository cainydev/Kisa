<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bottles\BottleResource;
use App\Models\Bottle;
use App\Models\Order;
use App\Models\OrderPosition;
use App\Models\Variant;
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

class NecessaryBottle extends Widget implements HasSchemas, HasActions, HasTable
{
    use InteractsWithSchemas;
    use InteractsWithActions;
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
                            ->options(Variant::pluck('size')->unique()->sort()->mapWithKeys(fn($size) => [$size => $size . 'g']))
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
            ->records(function (array $filters): Collection {
                $maxDateOption = $filters['max_date']['max_date'] ?? 'lastweek';
                $maxDate = match ($maxDateOption) {
                    'yesterday' => Carbon::yesterday()->startOfDay(),
                    'last3days' => Carbon::now()->subDays(3)->startOfDay(),
                    'lastweek' => Carbon::now()->subDays(7)->startOfDay(),
                    'lastmonth' => Carbon::now()->subMonth()->startOfDay(),
                    default => Carbon::now()->subDays(7)->startOfDay(),
                };
                $extrapolateMonths = filled($filters['extrapolate_months']['extrapolate_months'] ?? null)
                    ? (int)$filters['extrapolate_months']['extrapolate_months']
                    : $this->defaultExtrapolateMonths;
                $extrapolateMaxSize = filled($filters['extrapolate_max_size']['extrapolate_max_size'] ?? null)
                    ? (int)$filters['extrapolate_max_size']['extrapolate_max_size']
                    : $this->defaultExtrapolateMaxSize;
                $roundUp = $filters['round_up_quantity']['round_up_quantity'] ?? 'none';

                // fetch order positions for paid but not shipped orders since maxDate
                $positions = Order::with(['positions.variant', 'positions.order'])
                    ->whereNotNull('paid_at')
                    ->whereNull('shipped_at')
                    ->where('created_at', '>=', $maxDate)
                    ->whereHas('positions')
                    ->get()
                    ->flatMap(fn(Order $order) => $order->positions->map(fn(OrderPosition $p) => $p->setRelation('order', $order)));

                // Build records grouped by variant_id
                $grouped = $positions->groupBy(fn(OrderPosition $p) => $p->variant_id);

                return $grouped->mapWithKeys(function ($group) use ($extrapolateMonths, $extrapolateMaxSize, $roundUp) {
                    $variant = $group->first()->variant ?? Variant::find($group->first()->variant_id);
                    $variantLabel = $variant ? ($variant->title ?? $variant->name ?? '#' . $variant->id) : ('#' . $group->first()->variant_id);
                    $stock = $variant?->stock ?? 0;
                    $orderRefs = $group->map(fn($p) => $p->order?->order_number ?? $p->order?->reference ?? $p->order_id)->unique()->values()->all();
                    $averageMonthlySales = $variant?->average_monthly_sales ?? 0;
                    $minBatchSize = 1;
                    if ($variant && $variant->stock < 0) {
                        if ($variant->size <= $extrapolateMaxSize) {
                            $projectedNeeded = $extrapolateMonths * $averageMonthlySales;
                            $minNeededQuantity = max($minBatchSize, (int)ceil($projectedNeeded - $stock));
                        } else {
                            // For big/special sizes, just sum up the deficit for all positions
                            $minNeededQuantity = $group->sum(function ($p) use ($variant) {
                                $stock = $variant?->stock ?? 0;
                                return max(0, $p->quantity - $stock);
                            });
                        }
                        if ($roundUp !== 'none') {
                            $roundValue = (int)$roundUp;
                            $minNeededQuantity = $roundValue * (int)ceil($minNeededQuantity / $roundValue);
                        }
                        $perVariantHerbs = method_exists($variant, 'herbsNeededFor') ? ($variant->herbsNeededFor($minNeededQuantity) ?: []) : [];
                        return [
                            $variant->id => [
                                'variant_id' => $variant->id,
                                'variant_label' => $variantLabel,
                                'stock' => $stock,
                                'min_needed_quantity' => $minNeededQuantity,
                                'order_references' => $orderRefs,
                                'per_variant_herbs' => $perVariantHerbs,
                                'original_positions' => $group->pluck('id')->all(),
                            ],
                        ];
                    }
                    return [];
                });
            })
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
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
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
                        $bottle = Bottle::create([
                            'date' => now(),
                            'user_id' => auth()->user()->id,
                            'note' => 'Auto bottle ' . now()->format('Y-m-d H:i'),
                        ]);
                        foreach ($records as $rec) {
                            $roundUp = $this->filters['round_up_quantity'] ?? 'none';
                            $finalQuantity = $rec['min_needed_quantity'];
                            if ($roundUp !== 'none') {
                                $roundValue = (int)$roundUp;
                                $finalQuantity = $roundValue * (int)ceil($finalQuantity / $roundValue);
                            }
                            $bottle->positions()->create([
                                'variant_id' => $rec['variant_id'],
                                'count' => $finalQuantity,
                            ]);
                        }
                        return redirect(BottleResource::getUrl('edit', ['record' => $bottle->id]));
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
                        $bottle = Bottle::create([
                            'date' => now(),
                            'user_id' => auth()->user()->id,
                            'note' => 'Auto bottle ' . now()->format('Y-m-d H:i'),
                        ]);
                        foreach ($records as $rec) {
                            $roundUp = $this->filters['round_up_quantity'] ?? 'none';
                            $finalQuantity = $rec['min_needed_quantity'];
                            if ($roundUp !== 'none') {
                                $roundValue = (int)$roundUp;
                                $finalQuantity = $roundValue * (int)ceil($finalQuantity / $roundValue);
                            }
                            $bottle->positions()->create([
                                'variant_id' => $rec['variant_id'],
                                'count' => $finalQuantity,
                            ]);
                        }
                        return redirect(BottleResource::getUrl('edit', ['record' => $bottle->id]));
                    })
                    ->requiresConfirmation()
                    ->color('primary'),
            ])
            ->emptyStateHeading('Keine anstehenden Abfüllungen notwendig.');
    }
}
