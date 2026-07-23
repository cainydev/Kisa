<?php

namespace App\Mcp\Tools;

use App\Models\Supplier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List all suppliers with their current organic control body (derived from their currently valid certificate).')]
class ListSuppliersTool extends Tool
{
    public function handle(Request $request): Response
    {
        $suppliers = Supplier::query()
            ->with('certificates.bioInspector')
            ->orderBy('shortname')
            ->get();

        if ($suppliers->isEmpty()) {
            return Response::text('No suppliers found.');
        }

        $lines = $suppliers->map(function (Supplier $s): string {
            $body = $s->currentBioInspector()?->label ?? 'kein gültiges Zertifikat';

            return "• #{$s->id} {$s->shortname} ({$s->company}) — Kontrollstelle: {$body}";
        })->implode("\n");

        return Response::text("Suppliers ({$suppliers->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
