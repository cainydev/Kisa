<?php

namespace App\Mcp\Tools;

use App\Models\Bag;
use App\Models\Ingredient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Traceability (Warenweg, read-only): for a herb charge, report where it came from (delivery, supplier, frozen organic certificate) and which bottlings/products consumed it. This is the upstream+downstream audit trail for one charge.')]
class TraceChargeTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'charge' => 'required|string',
        ], [
            'charge.required' => 'Provide the charge number to trace.',
        ]);

        $bags = Bag::withTrashed()
            ->with('herb', 'delivery.supplier')
            ->where('charge', $validated['charge'])
            ->get();

        if ($bags->isEmpty()) {
            return Response::error("No bag found with charge \"{$validated['charge']}\".");
        }

        $sections = $bags->map(function (Bag $bag): string {
            $supplier = $bag->delivery?->supplier;
            $oeko = $bag->delivery?->frozenOekoCode() ?? 'kein Zertifikat';
            $certNo = $bag->delivery?->certificateSummary()['certificate_number'] ?? '—';

            $upstream = "HERKUNFT:\n"
                ."  Rohstoff: {$bag->herb?->name}\n"
                .'  Lieferant: '.($supplier?->company ?? '—')." ({$supplier?->shortname})\n"
                .'  Lieferung #'.($bag->delivery?->id ?? '—').' am '.($bag->delivery?->delivered_date?->format('d.m.Y') ?? '—')."\n"
                ."  Kontrollstelle: {$oeko} (Zertifikat {$certNo})";

            $bottlings = Ingredient::query()
                ->where('bag_id', $bag->id)
                ->with('position.bottle', 'position.variant.product')
                ->get()
                ->map(function (Ingredient $ing): string {
                    $pos = $ing->position;
                    $product = $pos?->variant?->product?->name ?? '—';
                    $size = $pos?->variant ? Number::kilos($pos->variant->size) : '—';
                    $date = $pos?->bottle?->date?->format('d.m.Y') ?? '—';
                    $charge = $pos?->charge ?? '—';

                    return "  • {$product} {$size}, Abfüllung Charge {$charge} am {$date}";
                })
                ->implode("\n");

            $downstream = "VERWENDUNG (Abfüllungen):\n".($bottlings ?: '  (noch nicht verwendet)');

            return "=== Bag #{$bag->id}, Charge {$bag->charge} ===\n{$upstream}\n\n{$downstream}";
        })->implode("\n\n");

        return Response::text($sections);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'charge' => $schema->string()->description('Exact charge number to trace.')->required(),
        ];
    }
}
