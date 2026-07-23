<?php

namespace App\Mcp\Tools;

use App\Models\Herb;
use App\Models\Variant;
use App\Support\Stats\HerbStats;
use App\Support\Stats\VariantStats;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Analysis: a combined low-stock overview across both raw materials (herbs) and finished variants, listing everything projected to run out within the given horizon (default 30 days). One shot for "what needs attention?".')]
class StockOverviewTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'within_days' => 'nullable|integer|min:1|max:3650',
        ]);

        $withinDays = (int) ($validated['within_days'] ?? 30);
        $deadline = now()->addDays($withinDays);

        $herbs = Herb::query()->get()
            ->map(fn (Herb $h): array => ['herb' => $h, 'depletion' => HerbStats::for($h)->estimatedDepletionDate()])
            ->filter(fn ($r) => $r['depletion'] !== null && $r['depletion']->lessThanOrEqualTo($deadline))
            ->sortBy('depletion');

        $variants = Variant::query()->with('product')->get()
            ->map(fn (Variant $v): array => ['variant' => $v, 'depletion' => VariantStats::for($v)->estimatedDepletionDate()])
            ->filter(fn ($r) => $r['depletion'] !== null && $r['depletion']->lessThanOrEqualTo($deadline))
            ->sortBy('depletion');

        $herbLines = $herbs->map(fn ($r): string => "  • {$r['herb']->name} — erschöpft ".$r['depletion']->format('d.m.Y'))->implode("\n");
        $variantLines = $variants->map(fn ($r): string => "  • {$r['variant']->product?->name} ".number_format($r['variant']->size / 1000, 2).' kg — erschöpft '.$r['depletion']->format('d.m.Y'))->implode("\n");

        $text = "Bestandsübersicht — was in {$withinDays} Tagen zur Neige geht:\n\n"
            .'ROHSTOFFE ('.$herbs->count()."):\n".($herbLines ?: '  (nichts)')."\n\n"
            .'VARIANTEN ('.$variants->count()."):\n".($variantLines ?: '  (nichts)');

        return Response::text($text);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'within_days' => $schema->integer()->description('Horizon in days (default 30).'),
        ];
    }
}
