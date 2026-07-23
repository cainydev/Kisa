<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List all products with their type and the number of variants each has.')]
class ListProductsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $products = Product::query()
            ->with('type')
            ->withCount('variants')
            ->orderBy('name')
            ->get();

        if ($products->isEmpty()) {
            return Response::text('No products found.');
        }

        $lines = $products->map(function (Product $p): string {
            $type = $p->type?->name ?? '—';

            return "• #{$p->id} {$p->name} — Typ: {$type}, {$p->variants_count} Varianten";
        })->implode("\n");

        return Response::text("Products ({$products->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
