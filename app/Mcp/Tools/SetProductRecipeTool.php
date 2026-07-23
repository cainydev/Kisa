<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Replace a product\'s entire recipe with the given herbs and percentages. Every herb must already exist. Warns if the percentages do not sum to 100.')]
class SetProductRecipeTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'product' => 'required|string',
            'recipe' => 'required|array|min:1',
            'recipe.*.herb' => 'required|string',
            'recipe.*.percentage' => 'required|numeric|min:0|max:100',
        ], [
            'product.required' => 'Provide the product (name or id).',
            'recipe.required' => 'Provide the recipe as a list of {herb, percentage}.',
        ]);

        $product = $this->resolveProduct($validated['product']);
        if ($product === null) {
            return Response::error("No product found matching \"{$validated['product']}\".");
        }

        $recipe = [];
        foreach ($validated['recipe'] as $i => $row) {
            $herb = $this->resolveHerb($row['herb']);
            if ($herb === null) {
                return Response::error('Recipe row '.($i + 1).": no herb found matching \"{$row['herb']}\".");
            }
            $recipe[$herb->id] = ['percentage' => $row['percentage']];
        }

        $product->herbs()->sync($recipe);

        $sum = collect($recipe)->sum(fn ($r) => (float) $r['percentage']);
        $warn = abs($sum - 100.0) > 0.01 ? ' ⚠ Rezept summiert auf '.number_format($sum, 1).'%, nicht 100%.' : '';

        return Response::text(
            "Recipe for \"{$product->name}\" replaced with ".count($recipe).' Zutaten (Summe '.number_format($sum, 1).'%).'.$warn
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('Product name or numeric id.')->required(),
            'recipe' => $schema->array()->description('The full new recipe: list of {herb (name/id), percentage (0–100)}.')->required(),
        ];
    }
}
