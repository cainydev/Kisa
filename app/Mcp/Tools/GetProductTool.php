<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Herb;
use App\Models\Variant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get one product in detail: its type, full recipe (herbs and their percentages), and variants (size, sku, current stock).')]
class GetProductTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'product' => 'required|string',
        ], [
            'product.required' => 'Provide a product name or numeric id.',
        ]);

        $product = $this->resolveProduct($validated['product']);
        if ($product === null) {
            return Response::error("No product found matching \"{$validated['product']}\". Use list-products.");
        }

        $product->loadMissing('type', 'herbs', 'variants');

        $recipe = $product->herbs
            ->sortByDesc(fn (Herb $h) => $h->pivot->percentage)
            ->map(fn (Herb $h): string => "  • {$h->name}: {$h->pivot->percentage}%")
            ->implode("\n");

        $recipeSum = $product->herbs->sum(fn (Herb $h) => (float) $h->pivot->percentage);

        $variants = $product->variants
            ->sortBy('size')
            ->map(function (Variant $v): string {
                $size = number_format($v->size / 1000, 2);

                return "  • {$size} kg — SKU {$v->sku} (Bestand: {$v->stock})";
            })
            ->implode("\n");

        $text = "Product #{$product->id}: {$product->name}\n"
            .'Typ: '.($product->type?->name ?? '—').($product->type?->compound ? ' (Mischung)' : '')."\n\n"
            .'Rezept (Summe '.number_format($recipeSum, 1)."%):\n".($recipe ?: '  (kein Rezept)')."\n\n"
            .'Varianten:'."\n".($variants ?: '  (keine)');

        return Response::text($text);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('Product name or numeric id.')->required(),
        ];
    }
}
