<?php

namespace App\Mcp\Tools;

use App\Models\Herb;
use App\Support\Stats\HerbStats;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List all raw material herbs with their current stock (in grams) and supplier. Optionally show only herbs running low.')]
class ListHerbsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'only_low_stock_grams' => 'nullable|numeric|min:0',
        ], [
            'only_low_stock_grams.numeric' => 'Provide a gram threshold, e.g. 1000 to list herbs with less than 1kg in stock.',
        ]);

        $threshold = $validated['only_low_stock_grams'] ?? null;

        $rows = Herb::query()
            ->with('supplier')
            ->orderBy('name')
            ->get()
            ->map(fn (Herb $herb): array => [
                'herb' => $herb,
                'stock' => HerbStats::for($herb)->currentStock(),
            ])
            ->when($threshold !== null, fn ($c) => $c->filter(fn ($r) => $r['stock'] < (float) $threshold))
            ->sortBy('stock')
            ->values();

        if ($rows->isEmpty()) {
            return Response::text($threshold !== null
                ? "No herbs are below {$threshold}g in stock."
                : 'No herbs found.');
        }

        $lines = $rows->map(function (array $r): string {
            $herb = $r['herb'];
            $stock = number_format($r['stock'] / 1000, 2);
            $supplier = $herb->supplier?->shortname ?? '—';

            return "• #{$herb->id} {$herb->name} — {$stock} kg (Lieferant: {$supplier})";
        })->implode("\n");

        $header = $threshold !== null
            ? "Herbs below {$threshold}g in stock ({$rows->count()}):"
            : "Herbs ({$rows->count()}):";

        return Response::text("{$header}\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'only_low_stock_grams' => $schema->number()
                ->description('If set, only list herbs whose current stock is below this many grams (e.g. 1000 for under 1kg).'),
        ];
    }
}
