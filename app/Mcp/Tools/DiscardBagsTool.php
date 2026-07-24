<?php

namespace App\Mcp\Tools;

use App\Models\Bag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Discard (entsorgen) one or more bags by their numeric ids, in one call. Marks each bag as discarded and records the amount that was still in it. Unknown ids and already-discarded bags are reported and skipped rather than failing the whole batch. Look bags up first with find-bags-by-herb or find-bag-by-charge to get their ids.')]
class DiscardBagsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'bag_ids' => 'required|array|min:1',
            'bag_ids.*' => 'integer',
        ], [
            'bag_ids.required' => 'Provide one or more numeric bag ids to discard.',
        ]);

        $ids = array_values(array_unique($validated['bag_ids']));

        $bags = Bag::withTrashed()->with('herb')->whereIn('id', $ids)->get()->keyBy('id');

        $discarded = [];
        $skipped = [];

        foreach ($ids as $id) {
            $bag = $bags->get($id);

            if ($bag === null) {
                $skipped[] = "#{$id}: nicht gefunden";

                continue;
            }

            if ($bag->trashed()) {
                $skipped[] = "#{$id} (Charge {$bag->charge}): bereits entsorgt";

                continue;
            }

            $remaining = Number::kilos($bag->getCurrentWithTrashed());
            $lastUsed = $bag->lastBottledAt()?->format('d.m.Y') ?? 'nie';

            DB::transaction(fn () => $bag->discard());

            $discarded[] = "#{$id} Charge {$bag->charge} ({$bag->herb?->name}): {$remaining} entsorgt, zuletzt abgefüllt {$lastUsed}";
        }

        $parts = [];

        if ($discarded !== []) {
            $parts[] = 'Entsorgt ('.count($discarded)."):\n• ".implode("\n• ", $discarded);
        }

        if ($skipped !== []) {
            $parts[] = 'Übersprungen ('.count($skipped)."):\n• ".implode("\n• ", $skipped);
        }

        $text = implode("\n\n", $parts);

        return $discarded === []
            ? Response::error($text ?: 'Keine Säcke entsorgt.')
            : Response::text($text);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'bag_ids' => $schema->array()
                ->items($schema->integer())
                ->description('Numeric ids of the bags to discard, e.g. [12, 15, 18].')
                ->required(),
        ];
    }
}
