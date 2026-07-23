<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Herb;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new raw material herb, optionally linked to a supplier. Fails clearly if the supplier cannot be found or the herb already exists.')]
class CreateHerbTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fullname' => 'nullable|string|max:255',
            'supplier' => 'nullable|string',
        ], [
            'name.required' => 'Provide a short herb name (e.g. "Kamille").',
        ]);

        if (Herb::whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])->exists()) {
            return Response::error("A herb named \"{$validated['name']}\" already exists.");
        }

        $supplierId = null;
        if (! empty($validated['supplier'])) {
            $supplier = $this->resolveSupplier($validated['supplier']);

            if ($supplier === null) {
                return Response::error("No supplier found matching \"{$validated['supplier']}\". Use list-suppliers to see available suppliers.");
            }

            $supplierId = $supplier->id;
        }

        $herb = Herb::create([
            'name' => $validated['name'],
            'fullname' => $validated['fullname'] ?? $validated['name'],
            'supplier_id' => $supplierId,
        ]);

        $herb->loadMissing('supplier');

        return Response::text(
            "Created herb #{$herb->id} \"{$herb->name}\" (Vollname: {$herb->fullname})"
            .($herb->supplier ? ", Lieferant: {$herb->supplier->shortname}" : ', ohne Lieferant').'.'
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Short herb name, e.g. "Kamille".')
                ->required(),
            'fullname' => $schema->string()
                ->description('Full descriptive name, e.g. "Kamillenblüten ganz". Defaults to the short name.'),
            'supplier' => $schema->string()
                ->description('Supplier shortname, company, or id to link the herb to (optional).'),
        ];
    }
}
