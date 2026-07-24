<?php

namespace App\Mcp\Tools;

use App\Models\Bag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Find herb charges (bags) by their charge number: which herb, remaining amount, delivery, supplier and frozen control body. Matching charges across deliveries are all returned.')]
class FindBagByChargeTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'charge' => 'required|string',
        ], [
            'charge.required' => 'Provide a charge number to look up.',
        ]);

        $bags = Bag::withTrashed()
            ->with('herb', 'delivery.supplier')
            ->where('charge', 'like', '%'.$validated['charge'].'%')
            ->get();

        if ($bags->isEmpty()) {
            return Response::error("No bag found with charge matching \"{$validated['charge']}\".");
        }

        $lines = $bags->map(function (Bag $b): string {
            $remaining = number_format($b->getCurrentWithTrashed() / 1000, 2);
            $size = number_format($b->size / 1000, 2);
            $supplier = $b->delivery?->supplier?->shortname ?? '—';
            $oeko = $b->delivery?->frozenOekoCode() ?? 'kein Zertifikat';
            $date = $b->delivery?->delivered_date?->format('d.m.Y') ?? '—';
            $status = $b->trashed() ? ' — ENTSORGT' : '';

            return "• Bag #{$b->id} Charge {$b->charge}: {$b->herb?->name} — {$remaining}/{$size} kg übrig{$status}\n"
                ."    Lieferung #{$b->delivery?->id} von {$supplier} am {$date}, Kontrollstelle: {$oeko}";
        })->implode("\n");

        return Response::text("Bags matching charge \"{$validated['charge']}\" ({$bags->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'charge' => $schema->string()->description('Charge number (exact or partial).')->required(),
        ];
    }
}
