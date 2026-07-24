<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Variant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new variant (a fill size) of an existing product. The product is resolved by name or id.')]
class CreateVariantTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'product' => 'required|string',
            'size_grams' => 'required|numeric|min:1',
            'sku' => 'nullable|string|max:255',
            'ean' => 'nullable|string|max:255',
        ], [
            'product.required' => 'Provide the product (name or id) this variant belongs to.',
            'size_grams.required' => 'Provide the fill size in grams.',
        ]);

        $product = $this->resolveProduct($validated['product']);
        if ($product === null) {
            return Response::error("No product found matching \"{$validated['product']}\". Use list-products or create-product first.");
        }

        if (! empty($validated['sku']) && Variant::whereRaw('LOWER(sku) = ?', [mb_strtolower($validated['sku'])])->exists()) {
            return Response::error("A variant with SKU \"{$validated['sku']}\" already exists.");
        }

        $variant = $product->variants()->create([
            'size' => (int) $validated['size_grams'],
            'sku' => $validated['sku'] ?? null,
            'ean' => $validated['ean'] ?? null,
            'stock' => 0,
        ]);

        $size = Number::kilos($variant->size);

        return Response::text(
            "Created variant #{$variant->id} of \"{$product->name}\": {$size}"
            .($variant->sku ? ", SKU {$variant->sku}" : '').'.'
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('Product name or numeric id.')->required(),
            'size_grams' => $schema->number()->description('Fill size in grams.')->required(),
            'sku' => $schema->string()->description('SKU / order number (optional).'),
            'ean' => $schema->string()->description('EAN barcode (optional).'),
        ];
    }
}
