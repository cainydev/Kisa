<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Bag;
use App\Support\Stats\HerbStats;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get one herb in detail: current stock, estimated depletion date, which products use it (with recipe percentage), and its most recent charges.')]
class GetHerbTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'herb' => 'required|string',
        ], [
            'herb.required' => 'Provide a herb name (e.g. "Kamille") or its numeric id.',
        ]);

        $herb = $this->resolveHerb($validated['herb']);

        if ($herb === null) {
            return Response::error("No herb found matching \"{$validated['herb']}\". Use list-herbs to see available herbs.");
        }

        $stats = HerbStats::for($herb);
        $stock = number_format($stats->currentStock() / 1000, 2);
        $depletion = $stats->estimatedDepletionDate()?->format('d.m.Y') ?? 'unbekannt';

        $products = $herb->products()->get()
            ->map(fn ($p): string => "  • {$p->name} ({$p->pivot->percentage}%)")
            ->implode("\n");

        $recentBags = $herb->bags()
            ->with('delivery.supplier')
            ->latest('bestbefore')
            ->limit(5)
            ->get()
            ->map(function (Bag $b): string {
                $remaining = number_format($b->getCurrentWithTrashed() / 1000, 2);
                $supplier = $b->delivery?->supplier?->shortname ?? '—';

                return "  • Charge {$b->charge}: {$remaining} kg übrig (von {$supplier})";
            })
            ->implode("\n");

        $text = "Herb #{$herb->id}: {$herb->name}\n"
            ."Vollname: {$herb->fullname}\n"
            .'Lieferant: '.($herb->supplier?->shortname ?? '—')."\n"
            ."Aktueller Bestand: {$stock} kg\n"
            ."Voraussichtlich erschöpft: {$depletion}\n\n"
            .'Verwendet in Produkten:'."\n".($products ?: '  (keine)')."\n\n"
            .'Aktuelle Chargen:'."\n".($recentBags ?: '  (keine)');

        return Response::text($text);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'herb' => $schema->string()
                ->description('Herb name, fullname, or numeric id.')
                ->required(),
        ];
    }
}
