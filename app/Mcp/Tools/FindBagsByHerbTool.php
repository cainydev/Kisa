<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Bag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List all charges (bags) of a herb with their bag id, remaining amount, when they were last used in a bottling, delivery and supplier. Active (not yet discarded) bags only by default — set include_discarded to also list entsorgte bags. Use this to decide which bags to discard, then discard-bags with their ids.')]
class FindBagsByHerbTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'herb' => 'required|string',
            'include_discarded' => 'nullable|boolean',
        ], [
            'herb.required' => 'Provide a herb name or id.',
        ]);

        $herb = $this->resolveHerb($validated['herb']);

        if ($herb === null) {
            return Response::error("No herb found matching \"{$validated['herb']}\". Use list-herbs to see available herbs.");
        }

        $includeDiscarded = (bool) ($validated['include_discarded'] ?? false);

        $bags = $herb->bags()
            ->when($includeDiscarded, fn ($q) => $q->withTrashed())
            ->with('delivery.supplier', 'ingredients.position.bottle')
            ->orderBy('bestbefore')
            ->get();

        if ($bags->isEmpty()) {
            $scope = $includeDiscarded ? '' : ' active';

            return Response::error("No{$scope} bags found for herb \"{$herb->name}\".");
        }

        $lines = $bags->map(function (Bag $b): string {
            $remaining = Number::kilos($b->getCurrentWithTrashed());
            $size = Number::kilos($b->size);
            $supplier = $b->delivery?->supplier?->shortname ?? '—';
            $lastUsed = $b->lastBottledAt()?->format('d.m.Y') ?? 'nie';
            $status = $b->trashed() ? ' — ENTSORGT' : '';

            return "• Bag #{$b->id} Charge {$b->charge}: {$remaining}/{$size} übrig{$status}\n"
                ."    zuletzt abgefüllt: {$lastUsed}, Lieferant: {$supplier}";
        })->implode("\n");

        return Response::text("Bags for {$herb->name} ({$bags->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'herb' => $schema->string()
                ->description('Herb name (exact or partial) or numeric id.')
                ->required(),
            'include_discarded' => $schema->boolean()
                ->description('Also list already discarded (entsorgte) bags. Defaults to false.'),
        ];
    }
}
