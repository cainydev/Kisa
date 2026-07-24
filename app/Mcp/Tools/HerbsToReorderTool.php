<?php

namespace App\Mcp\Tools;

use App\Services\Stats\DepletionForecast;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Analysis: which herbs need reordering, i.e. herbs whose estimated stock will be depleted within the given number of days (default 30). Ordered most-urgent first.')]
class HerbsToReorderTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'within_days' => 'nullable|integer|min:1|max:3650',
        ], [
            'within_days.integer' => 'Provide a whole number of days, e.g. 30.',
        ]);

        $withinDays = (int) ($validated['within_days'] ?? 30);

        $rows = app(DepletionForecast::class)->herbs($withinDays);

        if ($rows->isEmpty()) {
            return Response::text("No herbs are projected to run out within {$withinDays} days.");
        }

        $lines = $rows->map(function (array $r): string {
            $herb = $r['herb'];
            $stock = Number::kilos($r['stock']);
            $when = $r['depletion']->format('d.m.Y');
            $days = (int) now()->diffInDays($r['depletion'], false);
            $supplier = $herb->supplier?->shortname ?? '—';

            return "• {$herb->name} — {$stock}, erschöpft am {$when} (in {$days} Tagen), Lieferant: {$supplier}";
        })->implode("\n");

        return Response::text("Herbs to reorder within {$withinDays} days ({$rows->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'within_days' => $schema->integer()
                ->description('Horizon in days for the depletion projection (default 30).'),
        ];
    }
}
