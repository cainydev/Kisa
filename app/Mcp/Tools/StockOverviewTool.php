<?php

namespace App\Mcp\Tools;

use App\Services\Stats\DepletionForecast;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Number;
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

        $forecast = app(DepletionForecast::class);
        $herbs = $forecast->herbs($withinDays);
        $variants = $forecast->variants($withinDays);

        $herbLines = $herbs->map(fn ($r): string => "  • {$r['herb']->name} — erschöpft ".$r['depletion']->format('d.m.Y'))->implode("\n");
        $variantLines = $variants->map(fn ($r): string => "  • {$r['variant']->product?->name} ".Number::kilos($r['variant']->size).' — erschöpft '.$r['depletion']->format('d.m.Y'))->implode("\n");

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
