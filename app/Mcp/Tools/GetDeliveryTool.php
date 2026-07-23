<?php

namespace App\Mcp\Tools;

use App\Models\Bag;
use App\Models\Delivery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get one delivery in detail: supplier, date, its bags (herb charges), and the frozen organic certificate snapshot (number, control body, validity, activities, categories).')]
class GetDeliveryTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'delivery_id' => 'required|integer',
        ], [
            'delivery_id.required' => 'Provide the numeric delivery id (see list-deliveries).',
        ]);

        $delivery = Delivery::with(['supplier', 'bags.herb'])->find($validated['delivery_id']);

        if ($delivery === null) {
            return Response::error("No delivery found with id {$validated['delivery_id']}.");
        }

        $bags = $delivery->bags
            ->map(function (Bag $b): string {
                $size = number_format($b->size / 1000, 2);

                return "  • Charge {$b->charge}: {$b->herb?->name} — {$size} kg".($b->bio ? ' (bio)' : ' (konventionell)');
            })
            ->implode("\n");

        $summary = $delivery->certificateSummary();
        if ($summary !== null) {
            $cert = "  Nummer: {$summary['certificate_number']}\n"
                ."  Kontrollstelle: {$summary['control_body']} ({$summary['control_body_code']})\n"
                .'  Gültig: '.($summary['valid_from'] ?? '—').' – '.($summary['valid_until'] ?? '—')."\n"
                .'  Tätigkeiten: '.(implode(', ', $summary['activities']) ?: '—')."\n"
                .'  Kategorien: '.(implode(', ', $summary['product_categories']) ?: '—');
        } else {
            $cert = '  (kein Zertifikat eingefroren)';
        }

        $text = "Delivery #{$delivery->id}\n"
            .'Lieferant: '.($delivery->supplier?->company ?? '—')." ({$delivery->supplier?->shortname})\n"
            .'Lieferdatum: '.$delivery->delivered_date->format('d.m.Y')."\n\n"
            .'Gebinde ('.$delivery->bags->count()."):\n".($bags ?: '  (keine)')."\n\n"
            ."Zertifikat (eingefroren bei Wareneingang):\n{$cert}";

        return Response::text($text);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'delivery_id' => $schema->integer()->description('Numeric delivery id.')->required(),
        ];
    }
}
