<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Delivery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Append one or more herb charges (bags) to an existing delivery. Each herb is resolved by name and must already exist.')]
class AddBagsToDeliveryTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'delivery_id' => 'required|integer',
            'bags' => 'required|array|min:1',
            'bags.*.herb' => 'required|string',
            'bags.*.charge' => 'required|string|max:255',
            'bags.*.size_grams' => 'required|numeric|min:1',
            'bags.*.bio' => 'nullable|boolean',
            'bags.*.specification' => 'nullable|string|max:255',
            'bags.*.best_before' => 'nullable|date',
        ], [
            'delivery_id.required' => 'Provide the numeric delivery id.',
            'bags.required' => 'Provide at least one bag.',
        ]);

        $delivery = Delivery::find($validated['delivery_id']);
        if ($delivery === null) {
            return Response::error("No delivery found with id {$validated['delivery_id']}.");
        }

        $resolved = [];
        foreach ($validated['bags'] as $i => $bag) {
            $herb = $this->resolveHerb($bag['herb']);
            if ($herb === null) {
                return Response::error('Bag '.($i + 1).": no herb found matching \"{$bag['herb']}\".");
            }
            $resolved[$i] = $herb;
        }

        DB::transaction(function () use ($validated, $delivery, $resolved): void {
            foreach ($validated['bags'] as $i => $bag) {
                $delivery->bags()->create([
                    'herb_id' => $resolved[$i]->id,
                    'charge' => $bag['charge'],
                    'size' => (int) $bag['size_grams'],
                    'bio' => $bag['bio'] ?? true,
                    'specification' => $bag['specification'] ?? '',
                    'bestbefore' => isset($bag['best_before'])
                        ? Carbon::parse($bag['best_before'])->toDateString()
                        : now()->addYears(2)->toDateString(),
                ]);
            }
        });

        $added = collect($validated['bags'])
            ->map(fn (array $b, int $i): string => "  • {$resolved[$i]->name} — Charge {$b['charge']}")
            ->implode("\n");

        return Response::text('Added '.count($validated['bags'])." Gebinde to delivery #{$delivery->id}:\n{$added}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'delivery_id' => $schema->integer()->description('Numeric delivery id.')->required(),
            'bags' => $schema->array()->description('Bags to add, same shape as create-delivery: {herb, charge, size_grams, bio?, specification?, best_before?}.')->required(),
        ];
    }
}
