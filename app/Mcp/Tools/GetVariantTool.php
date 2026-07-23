<?php

namespace App\Mcp\Tools;

use App\Models\Variant;
use App\Support\Stats\VariantStats;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get one product variant in detail: its product, size, sku/ean, current stock, sales velocity and estimated depletion date. Look it up by SKU or numeric id.')]
class GetVariantTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'variant' => 'required|string',
        ], [
            'variant.required' => 'Provide a variant SKU or numeric id.',
        ]);

        $identifier = $validated['variant'];
        $variant = is_numeric($identifier)
            ? Variant::with('product')->find((int) $identifier)
            : Variant::with('product')->whereRaw('LOWER(sku) = ?', [mb_strtolower($identifier)])->first();

        if ($variant === null) {
            return Response::error("No variant found matching \"{$identifier}\".");
        }

        $stats = VariantStats::for($variant);
        $size = number_format($variant->size / 1000, 2);
        $avgDaily = number_format($stats->averageDailySales(), 2);
        $depletion = $stats->estimatedDepletionDate()?->format('d.m.Y') ?? 'unbekannt';

        $text = "Variant #{$variant->id}\n"
            .'Produkt: '.($variant->product?->name ?? '—')."\n"
            ."Größe: {$size} kg\n"
            .'SKU: '.($variant->sku ?? '—').' · EAN: '.($variant->ean ?? '—')."\n"
            ."Bestand: {$variant->stock}\n"
            ."Ø Verkauf/Tag: {$avgDaily}\n"
            ."Voraussichtlich erschöpft: {$depletion}";

        return Response::text($text);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'variant' => $schema->string()->description('Variant SKU or numeric id.')->required(),
        ];
    }
}
