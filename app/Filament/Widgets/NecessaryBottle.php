<?php

namespace App\Livewire;

use Filament\Forms\Components\NumericInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
/*
class NecessaryBottle extends Widget implements HasActions, HasTable
{
    use InteractsWithSchemas;

    // form state (filters)
    public ?array $filters = [];

    // convenience defaults
    public Carbon $defaultMaxDate;
    public int $defaultExtrapolateMonths = 3;
    public int $defaultExtrapolateMaxSize = 200;

    public function mount(): void
    {
        $this->defaultMaxDate = Carbon::now()->subDays(7)->startOfDay();

        // initialize filters with defaults if not present
        $this->filters = $this->filters ?? [];
        $this->filters['max_date'] = $this->filters['max_date'] ?? $this->defaultMaxDate->toDateString();
        $this->filters['extrapolate_months'] = $this->filters['extrapolate_months'] ?? $this->defaultExtrapolateMonths;
        $this->filters['extrapolate_max_size'] = $this->filters['extrapolate_max_size'] ?? $this->defaultExtrapolateMaxSize;
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                DatePicker::make('max_date')
                    ->label('Look back since')
                    ->required()
                    ->reactive(),
                NumericInput::make('extrapolate_months')
                    ->label('Extrapolate months')
                    ->minValue(0)
                    ->reactive(),
                NumericInput::make('extrapolate_max_size')
                    ->label('Extrapolate max size (g)')
                    ->minValue(0)
                    ->reactive(),
                TextInput::make('note')->label('Optional note for bottle')->placeholder('e.g. urgent pack'),
            ])
            ->statePath('filters');
    }

    public function table(Table $table): Table
    {
        // The records closure receives runtime parameters from Filament.
        // We accept ($search, $sortColumn, $sortDirection, $filtersFromTable) but only use $search here.
        return $table
            ->records(function (?string $search = null, ?string $sortColumn = null, ?string $sortDirection = null, array $filtersFromTable = []): Collection {
                // read current filter state (livewire form)
                $maxDate = isset($this->filters['max_date']) && filled($this->filters['max_date'])
                    ? Carbon::parse($this->filters['max_date'])->startOfDay()
                    : $this->defaultMaxDate;

                // fetch order positions for paid but not shipped orders since maxDate
                $positions = Order::with(['positions.variant', 'positions.order'])
                    ->whereNotNull('paid_at')
                    ->whereNull('shipped_at')
                    ->where('created_at', '>=', $maxDate)
                    ->whereHas('positions')
                    ->get()
                    ->flatMap(fn(Order $order) => $order->positions->map(fn(OrderPosition $p) => $p->setRelation('order', $order)));

                // Build records keyed by order_position id: so selection keys map to DB ids
                $records = $positions->mapWithKeys(function (OrderPosition $p) {
                    $variant = $p->variant ?? Variant::find($p->variant_id);
                    $variantLabel = $variant ? ($variant->title ?? $variant->name ?? '#' . $p->variant_id) : ('#' . $p->variant_id);
                    $orderRef = $p->order?->reference ?? $p->order_id;

                    // compute per-variant herbs if you want to display them
                    $perVariantHerbs = [];
                    if ($variant) {
                        // decide how many to create for this position (simple: $p->quantity)
                        $perVariantHerbs = method_exists($variant, 'herbsNeededFor') ? ($variant->herbsNeededFor($p->quantity) ?: []) : [];
                    }

                    return [
                        $p->id => [
                            'order_position_id' => $p->id,
                            'order_id' => $p->order_id,
                            'order_reference' => $orderRef,
                            'variant_id' => $p->variant_id,
                            'variant_label' => $variantLabel,
                            'quantity' => (int)$p->quantity,
                            'per_variant_herbs' => $perVariantHerbs,
                            // keep original input parameters from the position if you have them:
                            'original_input' => $p->attributesToArray(), // full raw attributes for reference
                        ],
                    ];
                });

                // apply simple search across order_reference and variant_label
                if (filled($search)) {
                    $needle = Str::lower($search);
                    $records = $records->filter(fn(array $rec) => Str::contains(Str::lower((string)$rec['variant_label']), $needle) || Str::contains(Str::lower((string)$rec['order_reference']), $needle));
                }

                // return a Collection as Filament accepts either array or collection for records()
                return $records->values();
            })
            ->columns([
                TextColumn::make('order_reference')
                    ->label('Order')
                    ->getStateUsing(fn($record) => $record['order_reference'] ?? null)
                    ->searchable(isIndividual: true)
                    ->wrap(),
                TextColumn::make('variant_label')
                    ->label('Variant')
                    ->getStateUsing(fn($record) => $record['variant_label'] ?? null)
                    ->wrap(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->getStateUsing(fn($record) => (string)($record['quantity'] ?? 0)),
                TextColumn::make('herbs')
                    ->label('Herbs needed')
                    ->formatStateUsing(fn($state, $record) => collect($record['per_variant_herbs'] ?? [])
                        ->map(fn($grams, $herbId) => sprintf(
                            '%s: %dg',
                            Herb::find($herbId)?->name ?? ("Herb#{$herbId}"),
                            (int)$grams
                        ))->join(', '))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('original_input')
                    ->label('Original input (debug)')
                    ->formatStateUsing(fn($state, $record) => json_encode($record['original_input'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->wrap()
                    ->toggleable(),
            ])
            ->sortable() // basic sortable UI; sorting is ignored for custom records unless you implement it in records() (see docs)
            ->searchable()
            ->selectable() // allow selecting rows
            ->bulkActions([
                BulkAction::make('createBottle')
                    ->label('Create Bottle from selected')
                    ->action(function (array $selectedKeys, array $records) {
                        // selectedKeys are the record keys (order_position ids)
                        // fetch the selected order positions
                        $positions = OrderPosition::whereIn('id', $selectedKeys)->with(['variant', 'order'])->get();

                        if ($positions->isEmpty()) {
                            $this->dispatchBrowserEvent('filament-notification', [
                                'type' => 'warning',
                                'message' => 'No positions selected.',
                            ]);
                            return;
                        }

                        // Create the Bottle model (adjust fields to your Bottle model)
                        if (!class_exists(Bottle::class)) {
                            // model not available — abort gracefully
                            $this->dispatchBrowserEvent('filament-notification', [
                                'type' => 'danger',
                                'message' => 'Bottle model not found. Please add App\Models\Bottle or adjust the action.',
                            ]);

                            return;
                        }

                        $note = $this->filters['note'] ?? null;
                        $bottle = Bottle::create([
                            'title' => 'Auto bottle ' . now()->format('Y-m-d H:i'),
                            'note' => $note,
                        ]);

                        // create positions on the bottle (adjust to your relationships)
                        foreach ($positions as $pos) {
                            if (method_exists($bottle, 'positions')) {
                                // if relation exists, create via relation
                                $bottle->positions()->create([
                                    'variant_id' => $pos->variant_id,
                                    'quantity' => $pos->quantity,
                                    'source_order_position_id' => $pos->id,
                                ]);
                            } else {
                                // fallback: try a BottlePosition model if that exists
                                if (class_exists(BottlePosition::class)) {
                                    BottlePosition::create([
                                        'bottle_id' => $bottle->id,
                                        'variant_id' => $pos->variant_id,
                                        'quantity' => $pos->quantity,
                                        'source_order_position_id' => $pos->id,
                                    ]);
                                }
                            }
                        }

                        // Redirect to the created bottle. Adjust route name as appropriate for your app.
                        try {
                            // Prefer a named route 'bottles.show' if present
                            return redirect()->route('bottles.show', ['bottle' => $bottle->id]);
                        } catch (\Throwable $e) {
                            // If route doesn't exist, redirect to a simple path
                            return redirect('/bottles/' . $bottle->id);
                        }
                    })
                    ->requiresConfirmation()
                    ->color('primary'),
            ])
            ->emptyState('No outstanding positions found.');
    }

    public function render(): string
    {
        return "{{ $this->form }}{{ $this->table }}";
    }
}
*/
