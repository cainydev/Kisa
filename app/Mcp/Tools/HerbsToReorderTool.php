<?php

namespace App\Mcp\Tools;

use App\Models\Herb;
use App\Support\Stats\HerbStats;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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
        $deadline = now()->addDays($withinDays);

        $rows = Herb::query()->with('supplier')->get()
            ->map(function (Herb $herb): array {
                $stats = HerbStats::for($herb);

                return [
                    'herb' => $herb,
                    'stock' => $stats->currentStock(),
                    'depletion' => $stats->estimatedDepletionDate(),
                ];
            })
            ->filter(fn ($r) => $r['depletion'] !== null && $r['depletion']->lessThanOrEqualTo($deadline))
            ->sortBy('depletion')
            ->values();

        if ($rows->isEmpty()) {
            return Response::text("No herbs are projected to run out within {$withinDays} days.");
        }

        $lines = $rows->map(function (array $r): string {
            $herb = $r['herb'];
            $stock = number_format($r['stock'] / 1000, 2);
            $when = $r['depletion']->format('d.m.Y');
            $days = (int) now()->diffInDays($r['depletion'], false);
            $supplier = $herb->supplier?->shortname ?? '—';

            return "• {$herb->name} — {$stock} kg, erschöpft am {$when} (in {$days} Tagen), Lieferant: {$supplier}";
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
