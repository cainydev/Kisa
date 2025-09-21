<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bottles\BottleResource;
use App\Models\Bottle;
use App\Models\Variant;
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

class NextBottles extends Widget implements HasTable, HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public int $maxSize = 200;
    public int $coverMonths = 3;
    public int $minItems = 5;
    public int $maxEntries = 15;
    protected string $view = 'filament.widgets.next-bottles';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Nächste Abfüllungen')
            ->description('Wähle die Varianten aus, die abgefüllt werden sollen.')
            ->filters([
                Filter::make('extrapolate_months')
                    ->schema([
                        TextInput::make('extrapolate_months')
                            ->label('Hochrechnung Monate')
                            ->numeric()
                            ->minValue(1)
                            ->default(3)
                            ->live(),
                    ]),
                Filter::make('extrapolate_max_size')
                    ->schema([
                        Select::make('extrapolate_max_size')
                            ->label('Hochrechnen bis Größe (g)')
                            ->options(Variant::pluck('size')->unique()->sort()->mapWithKeys(fn($size) => [$size => $size . 'g']))
                            ->native(false)
                            ->default(200)
                            ->live(),
                    ]),
                Filter::make('round_up_quantity')
                    ->schema([
                        Select::make('round_up_quantity')
                            ->label('Menge aufrunden auf')
                            ->options([
                                'none' => 'Keine Aufrundung',
                                '5' => '5',
                                '10' => '10',
                                '15' => '15',
                                '20' => '20',
                                '25' => '25',
                            ])
                            ->default('none')
                            ->live(),
                    ]),
            ], FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(['xs' => 1, 'md' => 3])
            ->records(function (array $filters): Collection {
                $extrapolateMonths = filled($filters['extrapolate_months']['extrapolate_months'] ?? null)
                    ? (int)$filters['extrapolate_months']['extrapolate_months']
                    : $this->coverMonths;
                $extrapolateMaxSize = filled($filters['extrapolate_max_size']['extrapolate_max_size'] ?? null)
                    ? (int)$filters['extrapolate_max_size']['extrapolate_max_size']
                    : $this->maxSize;
                $roundUp = $filters['round_up_quantity']['round_up_quantity'] ?? 'none';

                $variants = Variant::where('size', '<=', $extrapolateMaxSize)
                    ->where('stock', '<=', 0)
                    ->whereHas('product', function ($q) {
                        $q->where('exclude_from_statistics', false)
                            ->whereHas('type', function ($q2) {
                                $q2->where('exclude_from_statistics', false);
                            });
                    })
                    ->with('product')
                    ->get();

                // Sort by stock - (average_monthly_sales * extrapolate_months)
                $variants = $variants->sortBy(fn($v) => $v->stock - ($v->average_monthly_sales * $extrapolateMonths))->take($this->maxEntries);

                return $variants->map(function (Variant $v) use ($extrapolateMonths, $roundUp) {
                    $needed = max($this->minItems, intval($extrapolateMonths * $v->average_monthly_sales - $v->stock));
                    if ($roundUp !== 'none') {
                        $roundValue = (int)$roundUp;
                        $needed = $roundValue * (int)ceil($needed / $roundValue);
                    }
                    return [
                        'variant_id' => $v->id,
                        'product_id' => $v->product_id,
                        'product_name' => $v->product?->name ?? '',
                        'variant_name' => $v->name,
                        'size' => $v->size,
                        'stock' => $v->stock,
                        'average_monthly_sales' => $v->average_monthly_sales,
                        'needed_count' => $needed,
                    ];
                });
            })
            ->columns([
                TextColumn::make('variant_name')->label('Variante'),
                TextColumn::make('size')->label('Größe (g)'),
                TextColumn::make('stock')
                    ->icon('billbee')
                    ->iconPosition(IconPosition::After)
                    ->label('Lagerbestand'),
                TextColumn::make('average_monthly_sales')->label('Ø Verkäufe/Monat'),
                TextColumn::make('needed_count')->label('Benötigte Menge'),
            ])
            ->selectable()
            ->headerActions([
                BulkAction::make('createBottle')
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
                            $bottle->positions()->create([
                                'variant_id' => $rec['variant_id'],
                                'count' => $rec['needed_count'],
                            ]);
                        }
                        return redirect(BottleResource::getUrl('edit', ['record' => $bottle->id]));
                    })
                    ->requiresConfirmation()
                    ->color('primary'),
            ])
            ->emptyStateHeading('Keine Varianten für Abfüllung gefunden.');
    }
}
