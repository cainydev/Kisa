<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Delivery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List recent deliveries, optionally filtered by supplier and/or date range. Shows the frozen organic certificate status of each (✓ has certificate snapshot, ⚠ none).')]
class ListDeliveriesTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'supplier' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $query = Delivery::query()->with('supplier')->latest('delivered_date');

        if (! empty($validated['supplier'])) {
            $supplier = $this->resolveSupplier($validated['supplier']);
            if ($supplier === null) {
                return Response::error("No supplier found matching \"{$validated['supplier']}\".");
            }
            $query->where('supplier_id', $supplier->id);
        }

        if (! empty($validated['from'])) {
            $query->whereDate('delivered_date', '>=', Carbon::parse($validated['from']));
        }
        if (! empty($validated['to'])) {
            $query->whereDate('delivered_date', '<=', Carbon::parse($validated['to']));
        }

        $deliveries = $query->limit((int) ($validated['limit'] ?? 25))->get();

        if ($deliveries->isEmpty()) {
            return Response::text('No deliveries match those filters.');
        }

        $lines = $deliveries->map(function (Delivery $d): string {
            $cert = $d->certificateSummary() !== null ? '✓ '.$d->frozenOekoCode() : '⚠ kein Zertifikat';
            $bags = $d->bags()->count();

            return "• #{$d->id} {$d->supplier?->shortname} am {$d->delivered_date->format('d.m.Y')} — {$bags} Gebinde — {$cert}";
        })->implode("\n");

        return Response::text("Deliveries ({$deliveries->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'supplier' => $schema->string()->description('Filter by supplier (shortname, company, or id).'),
            'from' => $schema->string()->description('Only deliveries on or after this date (YYYY-MM-DD).'),
            'to' => $schema->string()->description('Only deliveries on or before this date (YYYY-MM-DD).'),
            'limit' => $schema->integer()->description('Max deliveries to return (default 25).'),
        ];
    }
}
