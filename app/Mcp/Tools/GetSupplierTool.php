<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Delivery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get one supplier in detail: contact info, current control body and certificate, the herbs they supply, and their most recent deliveries.')]
class GetSupplierTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'supplier' => 'required|string',
        ], [
            'supplier.required' => 'Provide a supplier shortname, company, or id.',
        ]);

        $supplier = $this->resolveSupplier($validated['supplier']);

        if ($supplier === null) {
            return Response::error("No supplier found matching \"{$validated['supplier']}\". Use list-suppliers to see available suppliers.");
        }

        $supplier->loadMissing('certificates.bioInspector', 'herbs', 'deliveries');

        $current = $supplier->currentCertificate();
        $certLine = $current
            ? "{$current->certificate_number} ({$current->bioInspector?->label}), gültig bis ".($current->valid_until?->format('d.m.Y') ?? '—')
            : 'kein gültiges Zertifikat';

        $herbs = $supplier->herbs->pluck('name')->sort()->implode(', ') ?: '(keine)';

        $recentDeliveries = $supplier->deliveries()
            ->latest('delivered_date')
            ->limit(5)
            ->get()
            ->map(fn (Delivery $d): string => '  • #'.$d->id.' am '.$d->delivered_date->format('d.m.Y')
                .' — Zertifikat: '.($d->frozenOekoCode() ?? 'keins'))
            ->implode("\n");

        $text = "Supplier #{$supplier->id}: {$supplier->company} ({$supplier->shortname})\n"
            .'Kontakt: '.($supplier->contact ?: '—').' · '.($supplier->email ?: '—').' · '.($supplier->phone ?: '—')."\n"
            ."Aktuelle Kontrollstelle / Zertifikat: {$certLine}\n\n"
            ."Gelieferte Rohstoffe: {$herbs}\n\n"
            .'Letzte Lieferungen:'."\n".($recentDeliveries ?: '  (keine)');

        return Response::text($text);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'supplier' => $schema->string()
                ->description('Supplier shortname, company, or numeric id.')
                ->required(),
        ];
    }
}
