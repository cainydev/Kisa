<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a product with its type and optional recipe (herbs with percentages) in one call. The product type is matched by name. Every recipe herb must already exist. Warns if the recipe percentages do not sum to 100.')]
class CreateProductTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'recipe' => 'nullable|array',
            'recipe.*.herb' => 'required_with:recipe|string',
            'recipe.*.percentage' => 'required_with:recipe|numeric|min:0|max:100',
        ], [
            'name.required' => 'Provide a product name.',
            'type.required' => 'Provide a product type name (e.g. "Einzelkraut", "Mischung nach Draht").',
        ]);

        if (Product::whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])->exists()) {
            return Response::error("A product named \"{$validated['name']}\" already exists.");
        }

        $type = ProductType::query()
            ->get()
            ->first(fn (ProductType $t): bool => Str::lower($t->name) === Str::lower($validated['type'])
                || str_contains(Str::lower($t->name), Str::lower($validated['type'])));

        if ($type === null) {
            $available = ProductType::pluck('name')->implode(', ');

            return Response::error("No product type matching \"{$validated['type']}\". Available: {$available}.");
        }

        // Resolve recipe herbs up-front.
        $recipe = [];
        foreach ($validated['recipe'] ?? [] as $i => $row) {
            $herb = $this->resolveHerb($row['herb']);
            if ($herb === null) {
                return Response::error('Recipe row '.($i + 1).": no herb found matching \"{$row['herb']}\".");
            }
            $recipe[$herb->id] = ['percentage' => $row['percentage']];
        }

        $product = DB::transaction(function () use ($validated, $type, $recipe): Product {
            $product = Product::create([
                'name' => $validated['name'],
                'product_type_id' => $type->id,
            ]);

            if ($recipe !== []) {
                $product->herbs()->sync($recipe);
            }

            return $product;
        });

        $sum = collect($recipe)->sum(fn ($r) => (float) $r['percentage']);
        $warn = ($recipe !== [] && abs($sum - 100.0) > 0.01)
            ? ' ⚠ Rezept summiert auf '.number_format($sum, 1).'%, nicht 100%.'
            : '';

        return Response::text(
            "Created product #{$product->id} \"{$product->name}\" (Typ: {$type->name}) "
            .'mit '.count($recipe).' Rezept-Zutaten.'.$warn
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Product name.')->required(),
            'type' => $schema->string()->description('Product type name, e.g. "Einzelkraut" or "Mischung nach Draht".')->required(),
            'recipe' => $schema->array()->description('Optional recipe: list of {herb (name/id), percentage (0–100)}. Should sum to 100.'),
        ];
    }
}
