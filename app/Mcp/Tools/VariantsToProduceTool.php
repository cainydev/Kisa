<?php

namespace App\Mcp\Tools;

use App\Services\Stats\DepletionForecast;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Analysis: which variants should be produced/filled soon, i.e. variants whose stock is projected to deplete within the given number of days (default 30) based on sales velocity. Ordered most-urgent first.')]
class VariantsToProduceTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'within_days' => 'nullable|integer|min:1|max:3650',
        ]);

        $withinDays = (int) ($validated['within_days'] ?? 30);

        $rows = app(DepletionForecast::class)->variants($withinDays);

        if ($rows->isEmpty()) {
            return Response::text("No variants are projected to run out within {$withinDays} days (note: this relies on generated sales statistics).");
        }

        $lines = $rows->map(function (array $r): string {
            $v = $r['variant'];
            $size = Number::kilos($v->size);
            $when = $r['depletion']->format('d.m.Y');
            $days = (int) now()->diffInDays($r['depletion'], false);

            return "• {$v->product?->name} {$size} (SKU {$v->sku}) — Bestand {$v->stock}, erschöpft am {$when} (in {$days} Tagen)";
        })->implode("\n");

        return Response::text("Variants to produce within {$withinDays} days ({$rows->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'within_days' => $schema->integer()->description('Horizon in days for the depletion projection (default 30).'),
        ];
    }
}
