<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Bags\BagResource;
use App\Filament\Resources\Bottles\BottleResource;
use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Models\Bag;
use App\Models\BottlePosition;
use App\Models\Delivery;
use App\Models\Herb;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Variant;
use App\Support\PrintPdf;
use App\Support\Traceability\BioInspection;
use App\Support\Warenweg\GraphAccumulator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Audit-oriented traceability page ("Warenweg / Charge verfolgen").
 *
 * The operator picks what to look at — a Charge, or a specific Produkt,
 * Variante, Gebinde or Abfüllung from the database — optionally narrowed to a
 * date range. The page builds one layered supply-chain graph (Lieferant →
 * Lieferung → Gebinde → Abfüllung → Produkt) rendered with Cytoscape: the
 * chosen entity is the anchor, goods flow left→right, and compliance gaps are
 * flagged loudly. Every node carries a detail payload shown in a modal on
 * click, and an aggregation summary below the graph describes the shown data.
 *
 * A product commonly pulls from many bags (a blend), so the graph is a DAG with
 * genuine fan-in/fan-out, not a simple tree.
 */
class Warenweg extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationLabel = 'Warenweg';

    protected static ?string $title = 'Warenweg verfolgen';

    protected static string|null|UnitEnum $navigationGroup = NavigationGroup::Overview;

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.warenweg';

    #[Url]
    public string $type = 'charge';

    #[Url]
    public ?string $charge = null;

    #[Url]
    public ?int $entityId = null;

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    /**
     * Cached graph for the current request so the view can build it once and
     * reuse it for the summary/aggregations.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $graphCache = null;

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            'charge' => 'Charge',
            'herb' => 'Rohstoff',
            'product' => 'Produkt',
            'variant' => 'Variante',
            'delivery' => 'Lieferung',
            'bag' => 'Gebinde',
            'filling' => 'Abfüllung',
        ];
    }

    /**
     * The search/filter form lives in a header Action modal so its styling comes
     * entirely from Filament.
     */
    public function filterAction(): Action
    {
        return Action::make('filter')
            ->label($this->hasQuery() ? $this->activeLabel() : 'Auswählen')
            ->icon('heroicon-m-magnifying-glass')
            ->color($this->hasQuery() ? 'gray' : 'primary')
            ->fillForm([
                'type' => $this->type,
                'charge' => $this->charge,
                'entityId' => $this->entityId,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ])
            ->schema([
                Select::make('type')
                    ->label('Suchen nach')
                    ->options(static::typeLabels())
                    ->default('charge')
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('entityId', null)),

                TextInput::make('charge')
                    ->label('Chargennummer')
                    ->placeholder('z. B. 5858')
                    ->visible(fn (Get $get) => $get('type') === 'charge')
                    ->requiredIf('type', 'charge'),

                Select::make('entityId')
                    ->label(fn (Get $get) => static::typeLabels()[$get('type')] ?? 'Eintrag')
                    ->visible(fn (Get $get) => $get('type') !== 'charge')
                    ->searchable()
                    // Show a first page of options before anything is typed, then
                    // narrow as the user searches.
                    ->options(fn (Get $get) => $this->searchEntities($get('type'), ''))
                    ->getSearchResultsUsing(fn (Get $get, string $search) => $this->searchEntities($get('type'), $search))
                    ->getOptionLabelUsing(fn (Get $get, $value) => $this->entityLabel($get('type'), (int) $value))
                    ->requiredUnless('type', 'charge'),

                DatePicker::make('dateFrom')
                    ->label('Von (optional)')
                    ->native(false)
                    ->displayFormat('d.m.Y'),

                DatePicker::make('dateTo')
                    ->label('Bis (optional)')
                    ->native(false)
                    ->displayFormat('d.m.Y'),
            ])
            ->modalHeading('Warenweg anzeigen')
            ->modalSubmitActionLabel('Anzeigen')
            ->modalWidth('lg')
            ->action(function (array $data): void {
                $this->type = $data['type'];
                $this->charge = $data['type'] === 'charge' ? trim((string) $data['charge']) : null;
                $this->entityId = $data['type'] === 'charge' ? null : (int) $data['entityId'];
                $this->dateFrom = $data['dateFrom'] ?: null;
                $this->dateTo = $data['dateTo'] ?: null;
                $this->graphCache = null;
            });
    }

    public function clearAction(): Action
    {
        return Action::make('clear')
            ->label('Zurücksetzen')
            ->icon('heroicon-m-x-mark')
            ->color('gray')
            ->link()
            ->visible($this->hasQuery())
            ->action(function (): void {
                $this->reset(['charge', 'entityId', 'dateFrom', 'dateTo']);
                $this->type = 'charge';
                $this->graphCache = null;
            });
    }

    /**
     * Generate the printable traceability PDF on the page itself: Filament shows
     * the action's spinner while Browsershot renders, then the browser downloads
     * the file. No separate route/controller.
     *
     * Each entity type maps to one of five document shapes (the print view's
     * `mode`), each designed to stay small (1–2 pages) and show only what is
     * actually related to the subject.
     */
    public function printAction(): Action
    {
        return Action::make('print')
            ->label('Drucken')
            ->icon('heroicon-m-printer')
            ->color('gray')
            ->outlined()
            ->visible($this->hasQuery())
            ->action(function () {
                $data = match ($this->type) {
                    'delivery' => $this->printDeliveryData(),
                    'herb' => $this->printHerbData(),
                    'product', 'variant' => $this->printProductData(),
                    'filling' => $this->printFillingData(),
                    default => $this->printGebindeData(), // charge, bag
                };

                $pdf = PrintPdf::fromView('print.warenweg', array_merge([
                    'business' => config('business'),
                    'subjectType' => static::typeLabels()[$this->type] ?? 'Auswahl',
                    'dateFrom' => $this->dateFrom,
                    'dateTo' => $this->dateTo,
                    'printedAt' => now(),
                ], $data));

                return response()->streamDownload(
                    fn () => print ($pdf),
                    'warenweg-'.now()->format('Ymd-Hi').'.pdf',
                    ['Content-Type' => 'application/pdf'],
                );
            });
    }

    /**
     * Mode A — Gebinde/Charge: one (or few) bags, each with full origin, the
     * complete Wareneingangskontrolle, and where it was used.
     *
     * @return array<string, mixed>
     */
    protected function printGebindeData(): array
    {
        $with = ['herb', 'delivery.supplier', 'delivery.media'];

        $bags = $this->type === 'charge'
            ? Bag::withTrashed()->with($with)->where('charge', $this->charge)->get()
            : Bag::withTrashed()->with($with)->whereKey($this->entityId)->get();

        $rows = $bags->map(fn (Bag $bag) => $this->gebindeRow($bag));

        return [
            'mode' => 'gebinde',
            'subjectLabel' => $this->type === 'charge' ? "Charge {$this->charge}" : $this->entityLabel('bag', (int) $this->entityId),
            'rows' => $rows,
            'flags' => $this->flagsFromGebindeRows($rows),
        ];
    }

    /**
     * Mode B — Lieferung: the delivery once (origin + Wareneingangskontrolle),
     * then a compact table of the Gebinde it delivered. No per-bag usage.
     *
     * @return array<string, mixed>
     */
    protected function printDeliveryData(): array
    {
        $delivery = Delivery::with('supplier', 'media')->find($this->entityId);

        if (! $delivery) {
            return ['mode' => 'delivery', 'subjectLabel' => "#{$this->entityId}", 'flags' => []];
        }

        $checks = $delivery->bioInspection()->checks();

        $bags = Bag::withTrashed()->with('herb')->where('delivery_id', $delivery->id)->get()
            ->map(fn (Bag $bag) => [
                'herb' => $bag->herb->name,
                'charge' => $bag->charge,
                'specification' => $bag->specification,
                'size' => $bag->getSizeInKilo(),
                'bestbefore' => $bag->bestbefore,
                'bio' => (bool) $bag->bio,
            ])
            ->sortBy('herb')->values();

        $header = $this->originHeader($delivery);

        return [
            'mode' => 'delivery',
            'subjectLabel' => $this->entityLabel('delivery', (int) $this->entityId),
            'header' => $header,
            'checks' => $checks,
            'bags' => $bags,
            'flags' => $this->flagsFromHeader($header, $checks),
        ];
    }

    /**
     * Mode C — Rohstoff: the herb, a table of its Gebinde (with origin per row),
     * and a compact aggregated table of the Produkte it went into.
     *
     * @return array<string, mixed>
     */
    protected function printHerbData(): array
    {
        $herb = Herb::find($this->entityId);
        if (! $herb) {
            return ['mode' => 'herb', 'subjectLabel' => "#{$this->entityId}", 'flags' => []];
        }

        $bags = Bag::withTrashed()->with('delivery.supplier', 'delivery.media')
            ->where('herb_id', $herb->id)
            ->when($this->dateFrom || $this->dateTo, fn ($q) => $q->whereHas(
                'delivery',
                fn ($d) => $d
                    ->when($this->dateFrom, fn ($x) => $x->whereDate('delivered_date', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($x) => $x->whereDate('delivered_date', '<=', $this->dateTo))
            ))
            ->get();

        $gebinde = $bags->map(function (Bag $bag) {
            $delivery = $bag->delivery;

            return [
                'charge' => $bag->charge,
                'supplier' => $delivery?->supplier?->shortname ?? $delivery?->supplier?->company,
                'oeko_code' => $delivery?->frozenOekoCode(),
                'delivery_date' => $delivery?->delivered_date,
                'size' => $bag->getSizeInKilo(),
                'released' => (bool) $delivery?->bioInspection()->isApproved(),
                'certificate' => (bool) $delivery?->getFirstMedia('certificate'),
                'bio' => (bool) $bag->bio,
            ];
        })->sortByDesc('delivery_date')->values();

        // Products this herb's bags fed (scoped to this herb only).
        $products = Product::query()
            ->whereHas('variants.positions.ingredients', fn ($q) => $q->whereIn('bag_id', $bags->pluck('id')))
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => ['product' => $p->name])
            ->values();

        return [
            'mode' => 'herb',
            'subjectLabel' => $herb->name,
            'herbStock' => $this->grams($herb->currentStock),
            'gebinde' => $gebinde,
            'products' => $products,
            'flags' => $this->flagsFromGebindeTable($gebinde),
        ];
    }

    /**
     * Mode D — Produkt/Variante: recipe, variants, and this product's own
     * bottlings with the Gebinde used in each. Strictly scoped to the product.
     *
     * @return array<string, mixed>
     */
    protected function printProductData(): array
    {
        $variantIds = $this->type === 'variant'
            ? [(int) $this->entityId]
            : Variant::where('product_id', $this->entityId)->pluck('id')->all();

        $product = $this->type === 'variant'
            ? Variant::with('product.type', 'product.herbs')->find($this->entityId)?->product
            : Product::with('type', 'herbs')->find($this->entityId);

        $fillings = BottlePosition::with(['bottle', 'variant', 'ingredients.bag.herb', 'ingredients.bag.delivery.supplier', 'ingredients.bag.delivery.media'])
            ->whereIn('variant_id', $variantIds)
            ->when($this->dateFrom || $this->dateTo, fn ($q) => $q->whereHas(
                'bottle',
                fn ($b) => $b
                    ->when($this->dateFrom, fn ($x) => $x->whereDate('date', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($x) => $x->whereDate('date', '<=', $this->dateTo))
            ))
            ->get()
            ->sortByDesc(fn ($p) => $p->bottle?->date)
            ->values();

        $recipe = $product
            ? $product->herbs->map(fn (Herb $h) => ['herb' => $h->name, 'percentage' => (float) ($h->pivot->percentage ?? 0)])
                ->sortByDesc('percentage')->values()
            : collect();

        $variants = Variant::whereIn('id', $variantIds)->get()
            ->map(fn (Variant $v) => [
                'size' => $v->size,
                'ordernumber' => $v->ordernumber,
                'fillings' => $fillings->where('variant_id', $v->id)->count(),
            ])->sortBy('size')->values();

        $bottlings = $fillings->map(function (BottlePosition $p) {
            $bags = $p->ingredients->map->bag->filter()->unique('id')->sortBy(fn ($b) => $b->herb->name)->values();

            return [
                'charge' => $p->charge,
                'date' => $p->bottle?->date,
                'size' => $p->variant?->size,
                'count' => (int) $p->count,
                'bags' => $bags->map(fn (Bag $bag) => [
                    'herb' => $bag->herb->name,
                    'charge' => $bag->charge,
                    'supplier' => $bag->delivery?->supplier?->shortname ?? $bag->delivery?->supplier?->company,
                    'oeko_code' => $bag->delivery?->frozenOekoCode(),
                    'released' => (bool) $bag->delivery?->bioInspection()->isApproved(),
                    'certificate' => (bool) $bag->delivery?->getFirstMedia('certificate'),
                    'bio' => (bool) $bag->bio,
                ])->all(),
            ];
        });

        return [
            'mode' => 'product',
            'subjectLabel' => $this->entityLabel($this->type, (int) $this->entityId),
            'compound' => (bool) $product?->type?->compound,
            'recipe' => $recipe,
            'variants' => $variants,
            'bottlings' => $bottlings,
            'flags' => $this->flagsFromBottlings($bottlings),
        ];
    }

    /**
     * Mode E — Abfüllung: one bottling and its ingredient Gebinde (the recipe as
     * actually used), each with origin/compliance.
     *
     * @return array<string, mixed>
     */
    protected function printFillingData(): array
    {
        $position = BottlePosition::with(['bottle', 'variant.product', 'ingredients.bag.herb', 'ingredients.bag.delivery.supplier', 'ingredients.bag.delivery.media'])
            ->find($this->entityId);

        if (! $position) {
            return ['mode' => 'filling', 'subjectLabel' => "#{$this->entityId}", 'flags' => []];
        }

        $bags = $position->ingredients->map->bag->filter()->unique('id')
            ->sortBy(fn ($b) => $b->herb->name)
            ->map(fn (Bag $bag) => [
                'bag_id' => $bag->id,
                'herb' => $bag->herb->name,
                'charge' => $bag->charge,
                'supplier' => $bag->delivery?->supplier?->shortname ?? $bag->delivery?->supplier?->company,
                'oeko_code' => $bag->delivery?->frozenOekoCode(),
                'delivery_date' => $bag->delivery?->delivered_date,
                'released' => (bool) $bag->delivery?->bioInspection()->isApproved(),
                'certificate' => (bool) $bag->delivery?->getFirstMedia('certificate'),
                'bio' => (bool) $bag->bio,
            ])->values();

        return [
            'mode' => 'filling',
            'subjectLabel' => $this->entityLabel('filling', (int) $this->entityId),
            'filling' => [
                'product' => $position->variant?->product?->name,
                'size' => $position->variant?->size,
                'charge' => $position->charge,
                'date' => $position->bottle?->date,
                'count' => (int) $position->count,
            ],
            'ingredients' => $bags,
            'flags' => $this->flagsFromGebindeTable($bags),
        ];
    }

    // --- print helpers --------------------------------------------------------

    /**
     * Full origin + usage view model for one bag (Mode A rows).
     *
     * @return array<string, mixed>
     */
    protected function gebindeRow(Bag $bag): array
    {
        $delivery = $bag->delivery;
        $inspection = BioInspection::fromArray($delivery?->bio_inspection);

        $usage = BottlePosition::with('variant.product.herbs', 'bottle', 'ingredients')
            ->whereHas('ingredients', fn ($q) => $q->where('bag_id', $bag->id))
            ->get()
            ->map(function (BottlePosition $p) use ($bag) {
                // Recipe share of this herb (informational, as of today) and
                // the grams actually drawn from this bag, frozen at bottling.
                $herb = $p->variant?->product?->herbs->firstWhere('id', $bag->herb_id);
                $percentage = (float) ($herb?->pivot->percentage ?? 0);
                $grams = (float) ($p->ingredients->firstWhere('bag_id', $bag->id)?->amount ?? 0);

                return [
                    'product' => $p->variant?->product?->name ?? 'Unbekannt',
                    'size' => $p->variant?->size,
                    'charge' => $p->charge,
                    'date' => $p->bottle?->date,
                    'count' => (int) $p->count,
                    'percentage' => $percentage,
                    'grams' => $grams,
                ];
            })
            ->sortByDesc('date')->values();

        // Per-bag mass balance (grams): delivered − used − loss = remaining.
        $total = (float) $bag->size;
        $remaining = $bag->getCurrentWithTrashed();
        $loss = (float) $bag->trashed;
        $used = $total - $remaining - $loss;

        return array_merge($this->originHeader($delivery), [
            'bag_id' => $bag->id,
            'herb' => $bag->herb->name,
            'charge' => $bag->charge,
            'specification' => $bag->specification,
            'size' => $bag->getSizeInKilo(),
            'bio' => (bool) $bag->bio,
            'bestbefore' => $bag->bestbefore,
            'emptied' => $bag->trashed(),
            'balance' => [
                'delivered' => round($total),
                'used' => round(max($used, 0)),
                'loss' => round($loss),
                'remaining' => round(max($remaining, 0)),
            ],
            'checks' => $inspection->checks(),
            'usage' => $usage,
        ]);
    }

    /**
     * Shared origin fields for a delivery (supplier, Kontrollstelle, docs, release).
     *
     * @return array<string, mixed>
     */
    protected function originHeader(?Delivery $delivery): array
    {
        $supplier = $delivery?->supplier;

        return [
            'supplier' => $supplier?->company,
            'inspector' => $delivery?->frozenControlBody(),
            'oeko_code' => $delivery?->frozenOekoCode(),
            'delivery_date' => $delivery?->delivered_date,
            'released' => BioInspection::fromArray($delivery?->bio_inspection)->isApproved(),
            'documents' => [
                'Zertifikat' => (bool) $delivery?->getFirstMedia('certificate'),
                'Rechnung' => (bool) $delivery?->getFirstMedia('invoice'),
                'Lieferschein' => (bool) $delivery?->getFirstMedia('deliveryNote'),
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<string>
     */
    protected function flagsFromGebindeRows(Collection $rows): array
    {
        $flags = [];
        foreach ($rows as $row) {
            $prefix = "{$row['herb']} (Charge {$row['charge']})";
            $flags = array_merge($flags, $this->headerFlags($prefix, $row, $row['bio']));
            foreach ($row['checks'] as $check) {
                if (! $check['ok']) {
                    $flags[] = "{$prefix}: {$check['label']} — nicht erfüllt";
                }
            }
        }

        return array_values(array_unique($flags));
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array{label: string, ok: bool}>  $checks
     * @return list<string>
     */
    protected function flagsFromHeader(array $header, array $checks): array
    {
        $flags = $this->headerFlags('Lieferung', $header, true);
        foreach ($checks as $check) {
            if (! $check['ok']) {
                $flags[] = "Wareneingangskontrolle: {$check['label']} — nicht erfüllt";
            }
        }

        return array_values(array_unique($flags));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $gebinde
     * @return list<string>
     */
    protected function flagsFromGebindeTable(Collection $gebinde): array
    {
        $flags = [];
        foreach ($gebinde as $g) {
            $prefix = isset($g['herb']) ? "{$g['herb']} (Charge {$g['charge']})" : "Charge {$g['charge']}";
            $flags = array_merge($flags, $this->headerFlags($prefix, [
                'delivery_date' => $g['delivery_date'] ?? true,
                'released' => $g['released'],
                'oeko_code' => $g['oeko_code'],
                'supplier' => $g['supplier'],
                'documents' => ['Zertifikat' => $g['certificate']],
            ], $g['bio']));
        }

        return array_values(array_unique($flags));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $bottlings
     * @return list<string>
     */
    protected function flagsFromBottlings(Collection $bottlings): array
    {
        $flags = [];
        foreach ($bottlings as $b) {
            foreach ($b['bags'] as $bag) {
                $flags = array_merge($flags, $this->headerFlags("{$bag['herb']} (Charge {$bag['charge']})", [
                    'delivery_date' => true,
                    'released' => $bag['released'],
                    'oeko_code' => $bag['oeko_code'],
                    'supplier' => $bag['supplier'],
                    'documents' => ['Zertifikat' => $bag['certificate']],
                ], $bag['bio']));
            }
        }

        return array_values(array_unique($flags));
    }

    /**
     * Common compliance checks on an origin header.
     *
     * @param  array<string, mixed>  $h
     * @return list<string>
     */
    protected function headerFlags(string $prefix, array $h, bool $bio): array
    {
        $flags = [];
        if (! $bio) {
            $flags[] = "{$prefix}: nicht als Bio erfasst";
        }
        if (empty($h['delivery_date'])) {
            $flags[] = "{$prefix}: keiner Lieferung zugeordnet";
        }
        if (! empty($h['delivery_date']) && empty($h['released'])) {
            $flags[] = "{$prefix}: Lieferung nicht freigegeben";
        }
        if (! empty($h['delivery_date']) && empty($h['documents']['Zertifikat'])) {
            $flags[] = "{$prefix}: Zertifikat fehlt";
        }
        if (empty($h['oeko_code']) && ! empty($h['supplier'])) {
            $flags[] = "{$prefix}: Lieferant ohne Kontrollstelle";
        }

        return $flags;
    }

    public function hasQuery(): bool
    {
        return filled($this->charge) || filled($this->entityId);
    }

    /**
     * Re-anchor the trace onto a node the user clicked in the graph, so they can
     * walk the chain (e.g. from a product view into one of its bags). Only the
     * entity types the entry form supports (bag, filling) are valid targets.
     */
    public function traceNode(string $type, int $id): void
    {
        if (! in_array($type, ['bag', 'filling', 'delivery'], true)) {
            return;
        }

        $this->type = $type;
        $this->entityId = $id;
        $this->charge = null;
        $this->graphCache = null;
    }

    /**
     * Human label for the current selection, e.g. "Gebinde: Ringelblumen (5858)".
     */
    public function activeLabel(): string
    {
        $type = static::typeLabels()[$this->type] ?? 'Auswahl';

        if ($this->type === 'charge') {
            return "Charge: {$this->charge}";
        }

        return "{$type}: ".$this->entityLabel($this->type, (int) $this->entityId);
    }

    /**
     * @return array<int, string>
     */
    protected function searchEntities(string $type, string $search): array
    {
        $search = trim($search);

        return match ($type) {
            'herb' => Herb::query()
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')
                ->limit(50)
                ->pluck('name', 'id')
                ->all(),
            'product' => Product::query()
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')
                ->limit(50)
                ->pluck('name', 'id')
                ->all(),
            'variant' => Variant::query()
                ->with('product')
                ->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhere('ordernumber', 'like', "%{$search}%")
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (Variant $v) => [$v->id => $this->variantLabel($v)])
                ->all(),
            'bag' => Bag::withTrashed()
                ->with('herb')
                ->where(fn ($q) => $q->where('charge', 'like', "%{$search}%")
                    ->orWhereHas('herb', fn ($h) => $h->where('name', 'like', "%{$search}%")))
                ->latest()
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (Bag $b) => [$b->id => $this->bagLabel($b)])
                ->all(),
            'filling' => BottlePosition::query()
                ->with('variant.product')
                ->where('charge', 'like', "%{$search}%")
                ->latest()
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (BottlePosition $p) => [$p->id => $this->fillingLabel($p)])
                ->all(),
            'delivery' => Delivery::query()
                ->with('supplier')
                ->where(fn ($q) => $q->whereHas('supplier', fn ($s) => $s->where('shortname', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%"))
                    ->orWhere('delivered_date', 'like', "%{$search}%"))
                ->latest('delivered_date')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (Delivery $d) => [$d->id => $this->deliveryLabel($d)])
                ->all(),
            default => [],
        };
    }

    protected function entityLabel(string $type, int $id): string
    {
        return match ($type) {
            'herb' => Herb::find($id)?->name ?? "#{$id}",
            'product' => Product::find($id)?->name ?? "#{$id}",
            'variant' => ($v = Variant::with('product')->find($id)) ? $this->variantLabel($v) : "#{$id}",
            'bag' => ($b = Bag::withTrashed()->with('herb')->find($id)) ? $this->bagLabel($b) : "#{$id}",
            'filling' => ($p = BottlePosition::with('variant.product')->find($id)) ? $this->fillingLabel($p) : "#{$id}",
            'delivery' => ($d = Delivery::with('supplier')->find($id)) ? $this->deliveryLabel($d) : "#{$id}",
            default => "#{$id}",
        };
    }

    protected function variantLabel(Variant $variant): string
    {
        return sprintf('%s (%dg)', $variant->product?->name ?? 'Produkt', $variant->size);
    }

    /**
     * Graph label for an Abfüllung node: the concrete variant (product + size),
     * since a filling is always of one specific variant, not just a product.
     */
    protected function fillingNodeLabel(BottlePosition $position): string
    {
        $product = $position->variant?->product?->name ?? 'Unbekanntes Produkt';

        return $position->variant
            ? sprintf('%s (%dg)', $product, $position->variant->size)
            : $product;
    }

    protected function bagLabel(Bag $bag): string
    {
        return "{$bag->herb->name} · Charge {$bag->charge}";
    }

    protected function deliveryLabel(Delivery $delivery): string
    {
        $supplier = $delivery->supplier?->shortname ?? $delivery->supplier?->company ?? 'Lieferant';

        return "{$supplier} · ".($delivery->delivered_date?->format('d.m.Y') ?? '—');
    }

    protected function fillingLabel(BottlePosition $position): string
    {
        $name = $position->variant?->product?->name ?? 'Abfüllung';

        return "{$name} · Charge {$position->charge}";
    }

    public function hasResult(): bool
    {
        return $this->hasQuery() && ! empty($this->buildGraph()['nodes']);
    }

    /**
     * Build the trace graph for the current selection. Each entry type has its
     * own builder that shows the one direction/depth answering its question,
     * inserting Herb grouping tiers where a flat fan-out would explode:
     *
     *   Charge    → the batch node, chain both directions
     *   Gebinde   → Lieferant→Lieferung→Bag → the Abfüllungen it fed
     *   Abfüllung → the filling ← its bags (recipe) ← origin
     *   Lieferung → Lieferung → Rohstoff → its Gebinde        (grouped by herb)
     *   Rohstoff  → its Gebinde ← origin, and → Produkte it went into (grouped)
     *   Produkt   → Produkt → Rohstoff → Gebinde ← origin      (fan-in, grouped)
     *   Variante  → like Produkt, scoped to one size
     *
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>, anchor: string|null}
     */
    public function buildGraph(): array
    {
        if ($this->graphCache !== null) {
            return $this->graphCache;
        }

        if (! $this->hasQuery()) {
            return $this->graphCache = ['nodes' => [], 'edges' => [], 'anchor' => null];
        }

        $g = match ($this->type) {
            'charge' => $this->buildChargeGraph(),
            'bag' => $this->buildBagGraph((int) $this->entityId),
            'filling' => $this->buildFillingGraph((int) $this->entityId),
            'delivery' => $this->buildDeliveryGraph((int) $this->entityId),
            'herb' => $this->buildHerbGraph((int) $this->entityId),
            'product' => $this->buildProductGraph((int) $this->entityId),
            'variant' => $this->buildVariantGraph((int) $this->entityId),
            default => new GraphAccumulator,
        };

        return $this->graphCache = $g->result();
    }

    /**
     * A Charge may resolve to bags and/or fillings. Show each matched bag with
     * its origin and the fillings it fed, and each matched filling with its
     * recipe — the batch as a point in the chain, both directions.
     */
    protected function buildChargeGraph(): GraphAccumulator
    {
        $bags = $this->bagQuery()->where('charge', $this->charge)->get();
        $positions = $this->positionQuery()->where('charge', $this->charge)->get();

        $anchor = $bags->isNotEmpty()
            ? "bag:{$bags->first()->id}"
            : ($positions->isNotEmpty() ? "filling:{$positions->first()->id}" : null);

        $g = new GraphAccumulator($anchor);

        foreach ($bags as $bag) {
            $this->emitBagWithOrigin($g, $bag);
            foreach ($this->fillingsOf($bag) as $position) {
                $this->emitFilling($g, $position);
                $g->edge("bag:{$bag->id}", "filling:{$position->id}");
            }
        }

        foreach ($positions as $position) {
            $this->emitFilling($g, $position);
            foreach ($this->bagsOf($position) as $bag) {
                $this->emitBagWithOrigin($g, $bag);
                $g->edge("bag:{$bag->id}", "filling:{$position->id}");
            }
        }

        return $g;
    }

    /**
     * A single Gebinde: its origin, then forward to the Abfüllungen it fed.
     */
    protected function buildBagGraph(int $bagId): GraphAccumulator
    {
        $g = new GraphAccumulator("bag:{$bagId}");

        $bag = $this->bagQuery()->find($bagId);
        if (! $bag) {
            return $g;
        }

        $this->emitBagWithOrigin($g, $bag);

        foreach ($this->fillingsOf($bag) as $position) {
            $this->emitFilling($g, $position);
            $g->edge("bag:{$bag->id}", "filling:{$position->id}");
        }

        return $g;
    }

    /**
     * A single Abfüllung: fan-in to the bags that fed it (the recipe) and their
     * origin.
     */
    protected function buildFillingGraph(int $positionId): GraphAccumulator
    {
        $g = new GraphAccumulator("filling:{$positionId}");

        $position = $this->positionQuery()->find($positionId);
        if (! $position) {
            return $g;
        }

        $this->emitFilling($g, $position);

        foreach ($this->bagsOf($position) as $bag) {
            $this->emitBagWithOrigin($g, $bag);
            $g->edge("bag:{$bag->id}", "filling:{$position->id}");
        }

        return $g;
    }

    /**
     * A Lieferung: the delivery, then its Gebinde grouped under Rohstoff tiers so
     * a delivery of dozens of bags stays legible. No forward into fillings.
     */
    protected function buildDeliveryGraph(int $deliveryId): GraphAccumulator
    {
        $g = new GraphAccumulator("delivery:{$deliveryId}");

        $delivery = Delivery::with('supplier', 'media')->find($deliveryId);
        if (! $delivery) {
            return $g;
        }

        $this->emitSupplierDelivery($g, $delivery);
        $deliveryNode = "delivery:{$delivery->id}";

        $bags = $this->bagQuery()->where('delivery_id', $deliveryId)->get();

        foreach ($bags->groupBy('herb_id') as $herbBags) {
            $herbNode = $this->emitHerbGroup($g, $herbBags->first()->herb, $herbBags->count());
            $g->edge($deliveryNode, $herbNode);

            foreach ($herbBags as $bag) {
                $this->emitBag($g, $bag);
                $g->edge($herbNode, "bag:{$bag->id}");
            }
        }

        return $g;
    }

    /**
     * A Rohstoff: backward to its Gebinde and their origin, and forward to the
     * Produkte it went into (collapsed — drill a product for its fillings).
     */
    protected function buildHerbGraph(int $herbId): GraphAccumulator
    {
        $g = new GraphAccumulator("herb:{$herbId}");

        $herb = Herb::find($herbId);
        if (! $herb) {
            return $g;
        }

        $herbNode = $this->emitHerbGroup($g, $herb, isAnchor: true);

        $bags = $this->bagQuery()
            ->where('herb_id', $herbId)
            ->when($this->dateFrom || $this->dateTo, fn ($q) => $q->whereHas(
                'delivery',
                fn ($d) => $d
                    ->when($this->dateFrom, fn ($x) => $x->whereDate('delivered_date', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($x) => $x->whereDate('delivered_date', '<=', $this->dateTo))
            ))
            ->get();

        foreach ($bags as $bag) {
            $this->emitBagWithOrigin($g, $bag);
            // Origin flows into the herb group: Gebinde → Rohstoff.
            $g->edge("bag:{$bag->id}", $herbNode);
        }

        // Forward: distinct products this herb's bags fed, within the window.
        $products = Product::query()
            ->whereHas('variants.positions', function ($q) use ($bags) {
                $q->whereHas('ingredients', fn ($i) => $i->whereIn('bag_id', $bags->pluck('id')))
                    ->when($this->dateFrom || $this->dateTo, fn ($qq) => $qq->whereHas(
                        'bottle',
                        fn ($b) => $b
                            ->when($this->dateFrom, fn ($x) => $x->whereDate('date', '>=', $this->dateFrom))
                            ->when($this->dateTo, fn ($x) => $x->whereDate('date', '<=', $this->dateTo))
                    ));
            })
            ->get();

        foreach ($products as $product) {
            $this->emitProduct($g, $product);
            $g->edge($herbNode, "product:{$product->id}");
        }

        return $g;
    }

    /**
     * A Produkt: fan-in to the Rohstoffe of its recipe, each grouping the actual
     * Gebinde used and their origin. Answers "what is in it, is it certified".
     */
    protected function buildProductGraph(int $productId): GraphAccumulator
    {
        $g = new GraphAccumulator("product:{$productId}");

        $product = Product::with('type')->find($productId);
        if (! $product) {
            return $g;
        }

        $this->emitProduct($g, $product);

        $variantIds = Variant::where('product_id', $productId)->pluck('id');

        $this->emitRecipeFanIn($g, "product:{$productId}", $variantIds);

        return $g;
    }

    /**
     * A Variante: like a product's fan-in, scoped to the one size.
     */
    protected function buildVariantGraph(int $variantId): GraphAccumulator
    {
        $g = new GraphAccumulator("variant:{$variantId}");

        $variant = Variant::with('product')->find($variantId);
        if (! $variant) {
            return $g;
        }

        $variantNode = "variant:{$variantId}";
        $g->node($variantNode, [
            'type' => 'product',
            'label' => $variant->product?->name ?? 'Variante',
            'sublabel' => "{$variant->size} g",
            'meta' => null,
            'gap' => false,
            'gapReasons' => [],
            'badge' => null,
        ]);

        $this->emitRecipeFanIn($g, $variantNode, collect([$variantId]));

        return $g;
    }

    /**
     * Shared fan-in for product/variant: for the given variants, group the bags
     * actually used (in the window) under Rohstoff tiers, with origin.
     *
     * @param  Collection<int, int>  $variantIds
     */
    protected function emitRecipeFanIn(GraphAccumulator $g, string $rootNode, Collection $variantIds): void
    {
        $positionIds = BottlePosition::query()
            ->whereIn('variant_id', $variantIds)
            ->when($this->dateFrom || $this->dateTo, fn ($q) => $q->whereHas(
                'bottle',
                fn ($b) => $b
                    ->when($this->dateFrom, fn ($x) => $x->whereDate('date', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($x) => $x->whereDate('date', '<=', $this->dateTo))
            ))
            ->pluck('id');

        if ($positionIds->isEmpty()) {
            return;
        }

        $bags = $this->bagQuery()
            ->whereHas('ingredients', fn ($q) => $q->whereIn('bottle_position_id', $positionIds))
            ->get();

        foreach ($bags->groupBy('herb_id') as $herbBags) {
            $herbNode = $this->emitHerbGroup($g, $herbBags->first()->herb, $herbBags->count());
            $g->edge($herbNode, $rootNode);

            foreach ($herbBags as $bag) {
                $this->emitBagWithOrigin($g, $bag);
                $g->edge("bag:{$bag->id}", $herbNode);
            }
        }
    }

    // --- shared node emitters -------------------------------------------------

    /**
     * @return Builder<Bag>
     */
    protected function bagQuery()
    {
        return Bag::withTrashed()->with([
            'herb',
            'delivery.supplier',
            'delivery.media',
        ]);
    }

    /**
     * @return Builder<BottlePosition>
     */
    protected function positionQuery()
    {
        return BottlePosition::with(['bottle', 'variant.product.type']);
    }

    /**
     * @return Collection<int, BottlePosition>
     */
    protected function fillingsOf(Bag $bag): Collection
    {
        return $this->positionQuery()
            ->whereHas('ingredients', fn ($q) => $q->where('bag_id', $bag->id))
            ->get();
    }

    /**
     * @return Collection<int, Bag>
     */
    protected function bagsOf(BottlePosition $position): Collection
    {
        return $this->bagQuery()
            ->whereHas('ingredients', fn ($q) => $q->where('bottle_position_id', $position->id))
            ->get();
    }

    protected function emitBag(GraphAccumulator $g, Bag $bag): void
    {
        $reasons = [];
        if (! $bag->bio) {
            $reasons[] = 'Gebinde nicht als Bio erfasst';
        }
        if ($bag->delivery === null) {
            $reasons[] = 'Gebinde keiner Lieferung zugeordnet';
        }

        $g->node("bag:{$bag->id}", [
            'type' => 'bag',
            'label' => $bag->herb->name,
            'sublabel' => "Charge {$bag->charge}",
            'meta' => "{$bag->specification} · {$bag->getSizeInKilo()}",
            'gap' => ! empty($reasons),
            'gapReasons' => $reasons,
            'badge' => $bag->bio ? null : 'konv.',
        ]);
    }

    protected function emitBagWithOrigin(GraphAccumulator $g, Bag $bag): void
    {
        $this->emitBag($g, $bag);

        $delivery = $bag->delivery;
        if (! $delivery?->supplier) {
            return;
        }

        $this->emitSupplierDelivery($g, $delivery);

        $inspection = $delivery->bioInspection();
        $released = $inspection->isApproved();
        $openFindings = $inspection->openFindingCount();

        $g->edge("delivery:{$delivery->id}", "bag:{$bag->id}", gap: ! $released || $openFindings > 0);
    }

    protected function emitSupplierDelivery(GraphAccumulator $g, Delivery $delivery): void
    {
        $supplier = $delivery->supplier;
        if (! $supplier) {
            return;
        }

        $oekoCode = $delivery->frozenOekoCode();
        $controlBody = $delivery->frozenControlBody();
        $g->node("supplier:{$supplier->id}", [
            'type' => 'supplier',
            'label' => $supplier->shortname ?: $supplier->company,
            'sublabel' => $oekoCode,
            'meta' => $controlBody,
            'gap' => $oekoCode === null,
            'gapReasons' => $oekoCode === null ? ['Lieferung ohne Kontrollstellen-Snapshot'] : [],
            'badge' => $oekoCode,
        ]);

        $inspection = $delivery->bioInspection();
        $released = $inspection->isApproved();
        $openFindings = $inspection->openFindingCount();
        $hasCertificate = (bool) $delivery->getFirstMedia('certificate');

        $reasons = [];
        if (! $released) {
            $reasons[] = 'Lieferung nicht freigegeben';
        }
        if (! $hasCertificate) {
            $reasons[] = 'Zertifikat fehlt';
        }
        if ($openFindings > 0) {
            $reasons[] = 'Offene Punkte in der Eingangskontrolle';
        }

        $g->node("delivery:{$delivery->id}", [
            'type' => 'delivery',
            'label' => $delivery->delivered_date?->format('d.m.Y') ?? '—',
            'sublabel' => $released ? 'freigegeben' : 'gesperrt',
            'meta' => null,
            'gap' => ! empty($reasons),
            'gapReasons' => $reasons,
        ]);

        $g->edge("supplier:{$supplier->id}", "delivery:{$delivery->id}");
    }

    protected function emitFilling(GraphAccumulator $g, BottlePosition $position): void
    {
        $product = $position->variant?->product;

        $g->node("filling:{$position->id}", [
            'type' => 'filling',
            'label' => $this->fillingNodeLabel($position),
            'sublabel' => "Charge {$position->charge}",
            'meta' => trim(($position->bottle?->date?->format('d.m.Y') ?? '').' · '.$position->count.' Stk'),
            'count' => (int) $position->count,
            'gap' => false,
            'gapReasons' => [],
            'badge' => $product?->type?->compound ? 'Mischung' : null,
        ]);
    }

    protected function emitProduct(GraphAccumulator $g, Product $product): void
    {
        $g->node("product:{$product->id}", [
            'type' => 'product',
            'label' => $product->name,
            'sublabel' => 'Produkt',
            'meta' => null,
            'gap' => false,
            'gapReasons' => [],
            'badge' => $product->type?->compound ? 'Mischung' : null,
        ]);
    }

    /**
     * A Rohstoff grouping node, used to collapse many bags of one herb.
     */
    protected function emitHerbGroup(GraphAccumulator $g, Herb $herb, ?int $bagCount = null, bool $isAnchor = false): string
    {
        $id = "herb:{$herb->id}";

        $g->node($id, [
            'type' => 'herb',
            'label' => $herb->name,
            'sublabel' => $bagCount !== null ? ($bagCount.' '.($bagCount === 1 ? 'Gebinde' : 'Gebinde')) : 'Rohstoff',
            'meta' => null,
            'gap' => false,
            'gapReasons' => [],
            'badge' => null,
        ]);

        return $id;
    }

    /**
     * Lazily build the rich detail payload for a single clicked node. Kept out
     * of the graph build so expensive per-node work (bag fill-bar, media checks)
     * only runs for the one node the user opens, not every node up-front.
     *
     * @return array<string, mixed>|null
     */
    public function nodeDetail(string $nodeId): ?array
    {
        [$type, $id] = array_pad(explode(':', $nodeId, 2), 2, null);
        $id = (int) $id;

        return match ($type) {
            'supplier' => ($s = Supplier::with('certificates.bioInspector')->find($id)) ? $this->supplierDetail($s) : null,
            'delivery' => ($d = Delivery::with('supplier', 'media')->find($id)) ? $this->deliveryDetail($d) : null,
            'bag' => ($b = Bag::withTrashed()->with('herb')->find($id)) ? $this->bagDetail($b) : null,
            'filling' => ($p = BottlePosition::with('variant.product.type', 'bottle')->find($id)) ? $this->fillingDetail($p) : null,
            'herb' => ($h = Herb::find($id)) ? $this->herbDetail($h) : null,
            'product' => ($pr = Product::with('type', 'herbs')->find($id)) ? $this->productDetail($pr) : null,
            'variant' => ($v = Variant::with('product.type', 'product.herbs')->find($id)) ? $this->variantDetail($v) : null,
            default => null,
        };
    }

    /**
     * Aggregations describing the data currently shown in the graph.
     *
     * @return array<string, mixed>
     */
    public function getAggregates(): array
    {
        $graph = $this->buildGraph();
        $nodes = collect($graph['nodes'])->map->data;

        $suppliers = $nodes->where('type', 'supplier');
        $deliveries = $nodes->where('type', 'delivery');
        $bags = $nodes->where('type', 'bag');
        $fillings = $nodes->where('type', 'filling');

        $unitsOut = $fillings->sum(fn ($f) => (int) ($f['count'] ?? 0));
        $flagged = $nodes->filter(fn ($n) => $n['gap'] ?? false);

        // Group every gap reason across all nodes into "<reason> × N", so the
        // summary explains *what kind* of issue was found rather than dumping a
        // truncated list of node names.
        $reasonCounts = $flagged
            ->flatMap(fn ($n) => $n['gapReasons'] ?? [])
            ->countBy()
            ->sortDesc();

        return [
            'suppliers' => $suppliers->count(),
            'deliveries' => $deliveries->count(),
            'bags' => $bags->count(),
            'fillings' => $fillings->count(),
            'unitsOut' => $unitsOut,
            'flaggedNodes' => $flagged->count(),
            'gapGroups' => $reasonCounts->map(fn ($count, $reason) => [
                'reason' => $reason,
                'count' => $count,
            ])->values()->all(),
            'herbs' => $bags->pluck('label')->unique()->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function supplierDetail($supplier): array
    {
        $inspector = $supplier->currentBioInspector();

        return [
            'title' => $supplier->company,
            'rows' => array_values(array_filter([
                ['label' => 'Kurzname', 'value' => $supplier->shortname],
                ['label' => 'Kontrollstelle (aktuell)', 'value' => $inspector?->company],
                ['label' => 'Kontrollstellen-Nr. (aktuell)', 'value' => $inspector?->label, 'highlight' => true],
                $supplier->contact ? ['label' => 'Kontakt', 'value' => $supplier->contact] : null,
                $supplier->email ? ['label' => 'E-Mail', 'value' => $supplier->email] : null,
                $supplier->phone ? ['label' => 'Telefon', 'value' => $supplier->phone] : null,
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function deliveryDetail(Delivery $delivery): array
    {
        $inspection = $delivery->bioInspection();

        return [
            'title' => 'Lieferung vom '.($delivery->delivered_date?->format('d.m.Y') ?? '—'),
            'url' => $this->deliveryUrl($delivery),
            'urlLabel' => 'Zur Lieferung',
            'released' => $inspection->isApproved(),
            'checks' => $inspection->checks(),
            'documents' => array_values(array_filter([
                $this->documentRow($delivery, 'certificate', 'Zertifikat'),
                $this->documentRow($delivery, 'invoice', 'Rechnung'),
                $this->documentRow($delivery, 'deliveryNote', 'Lieferschein'),
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function bagDetail(Bag $bag): array
    {
        $total = (float) $bag->size;
        $free = $bag->getCurrentWithTrashed();
        $trashed = (float) $bag->trashed;
        $used = $total - $free - $trashed;

        return [
            'title' => $bag->herb->name,
            'url' => $this->bagUrl($bag),
            'urlLabel' => 'Zum Gebinde',
            // Consumption bar (verworfen / verbraucht / übrig), same segments as
            // the bag table column, precomputed in grams for the modal to draw.
            'amount' => [
                'total' => round($total),
                'trashed' => round($trashed),
                'used' => round(max($used, 0)),
                'free' => round(max($free, 0)),
            ],
            'rows' => array_values(array_filter([
                ['label' => 'Charge', 'value' => $bag->charge, 'highlight' => true],
                ['label' => 'Spezifikation', 'value' => $bag->specification],
                ['label' => 'Menge', 'value' => $bag->getSizeInKilo()],
                ['label' => 'Bio', 'value' => $bag->bio ? 'Ja' : 'Nein (konventionell)'],
                ['label' => 'MHD', 'value' => $bag->bestbefore?->format('d.m.Y')],
                $bag->steamed ? ['label' => 'Gedämpft', 'value' => $bag->steamed->format('d.m.Y')] : null,
                ['label' => 'Status', 'value' => $bag->trashed() ? 'Geleert / verworfen' : 'Aktiv'],
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fillingDetail(BottlePosition $position): array
    {
        $product = $position->variant?->product;

        return [
            'title' => $product?->name ?? 'Abfüllung',
            'url' => $this->bottleUrl($position),
            'urlLabel' => 'Zur Abfüllung',
            'count' => (int) $position->count,
            'rows' => array_values(array_filter([
                ['label' => 'Charge', 'value' => $position->charge, 'highlight' => true],
                ['label' => 'Abgefüllt am', 'value' => $position->bottle?->date?->format('d.m.Y')],
                ['label' => 'Menge', 'value' => $position->count.' Stück'],
                $position->variant ? ['label' => 'Variante', 'value' => $position->variant->size.'g'] : null,
                ['label' => 'Typ', 'value' => $product?->type?->compound ? 'Mischung' : 'Einzelkraut'],
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function herbDetail(Herb $herb): array
    {
        $bagCount = Bag::withTrashed()->where('herb_id', $herb->id)->count();
        $productCount = $herb->products()->count();

        return [
            'title' => $herb->name,
            'rows' => array_values(array_filter([
                $herb->fullname && $herb->fullname !== $herb->name
                    ? ['label' => 'Bezeichnung', 'value' => $herb->fullname]
                    : null,
                ['label' => 'Gebinde gesamt', 'value' => (string) $bagCount],
                ['label' => 'In Produkten', 'value' => (string) $productCount],
                ['label' => 'Aktueller Bestand', 'value' => $this->grams($herb->currentStock)],
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productDetail(Product $product): array
    {
        return [
            'title' => $product->name,
            'rows' => [
                ['label' => 'Typ', 'value' => $product->type?->compound ? 'Mischung' : 'Einzelkraut'],
                ['label' => 'Rohstoffe', 'value' => (string) $product->herbs->count()],
                ['label' => 'Varianten', 'value' => (string) $product->variants()->count()],
            ],
            'recipe' => $product->herbs
                ->map(fn (Herb $h) => [
                    'herb' => $h->name,
                    'percentage' => (float) ($h->pivot->percentage ?? 0),
                ])
                ->sortByDesc('percentage')
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function variantDetail(Variant $variant): array
    {
        $product = $variant->product;

        return [
            'title' => $product?->name ?? 'Variante',
            'rows' => array_values(array_filter([
                ['label' => 'Größe', 'value' => $variant->size.' g', 'highlight' => true],
                $variant->ordernumber ? ['label' => 'Bestellnummer', 'value' => $variant->ordernumber] : null,
                ['label' => 'Typ', 'value' => $product?->type?->compound ? 'Mischung' : 'Einzelkraut'],
            ])),
            'recipe' => $product
                ? $product->herbs
                    ->map(fn (Herb $h) => [
                        'herb' => $h->name,
                        'percentage' => (float) ($h->pivot->percentage ?? 0),
                    ])
                    ->sortByDesc('percentage')
                    ->values()
                    ->all()
                : [],
        ];
    }

    protected function grams(float|int $g): string
    {
        return $g >= 1000
            ? Number::kilos($g, 1)
            : Number::grams($g);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function documentRow(Delivery $delivery, string $collection, string $label): ?array
    {
        $media = $delivery->getFirstMedia($collection);

        return [
            'label' => $label,
            'present' => (bool) $media,
            'url' => $media?->getFullUrl(),
        ];
    }

    protected function deliveryUrl(Delivery $delivery): ?string
    {
        try {
            return DeliveryResource::getUrl('edit', ['record' => $delivery]);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function bottleUrl(BottlePosition $position): ?string
    {
        if (! $position->bottle) {
            return null;
        }

        try {
            return BottleResource::getUrl('edit', ['record' => $position->bottle]);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function bagUrl(Bag $bag): ?string
    {
        try {
            return BagResource::getUrl('edit', ['record' => $bag]);
        } catch (\Throwable) {
            return null;
        }
    }
}
