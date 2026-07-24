<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Delivery;
use App\Models\User;
use App\Services\Traceability\CertificateSnapshotter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a delivery of herb charges from a supplier in one call: the delivery plus all its bags (each herb resolved by name), then automatically resolve and freeze the supplier\'s organic certificate valid on the delivery date. Reports which certificate was attached, or warns if none covers the date. Every herb must already exist.')]
class CreateDeliveryTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'supplier' => 'required|string',
            'delivered_date' => 'required|date',
            'bags' => 'required|array|min:1',
            'bags.*.herb' => 'required|string',
            'bags.*.charge' => 'required|string|max:255',
            'bags.*.size_grams' => 'required|numeric|min:1',
            'bags.*.bio' => 'nullable|boolean',
            'bags.*.specification' => 'nullable|string|max:255',
            'bags.*.best_before' => 'nullable|date',
        ], [
            'supplier.required' => 'Provide the supplier (shortname, company, or id).',
            'delivered_date.required' => 'Provide the delivery date (YYYY-MM-DD).',
            'bags.required' => 'Provide at least one bag with herb, charge and size_grams.',
            'bags.*.herb.required' => 'Each bag needs a herb name.',
            'bags.*.charge.required' => 'Each bag needs a charge number.',
            'bags.*.size_grams.required' => 'Each bag needs a size in grams.',
        ]);

        $supplier = $this->resolveSupplier($validated['supplier']);
        if ($supplier === null) {
            return Response::error("No supplier found matching \"{$validated['supplier']}\". Use list-suppliers or create-supplier first.");
        }

        // Resolve every herb up-front so we fail before writing anything.
        $resolved = [];
        foreach ($validated['bags'] as $i => $bag) {
            $herb = $this->resolveHerb($bag['herb']);
            if ($herb === null) {
                return Response::error('Bag '.($i + 1).": no herb found matching \"{$bag['herb']}\". Create it with create-herb or check the name.");
            }
            $resolved[$i] = $herb;
        }

        $user = User::query()->orderBy('id')->first();

        $delivery = DB::transaction(function () use ($validated, $supplier, $resolved, $user): Delivery {
            $delivery = Delivery::create([
                'supplier_id' => $supplier->id,
                'user_id' => $user?->id,
                'delivered_date' => Carbon::parse($validated['delivered_date'])->toDateString(),
                'bio_inspection' => ['approved' => false],
            ]);

            foreach ($validated['bags'] as $i => $bag) {
                $delivery->bags()->create([
                    'herb_id' => $resolved[$i]->id,
                    'charge' => $bag['charge'],
                    'size' => (int) $bag['size_grams'],
                    'bio' => $bag['bio'] ?? true,
                    'specification' => $bag['specification'] ?? '',
                    'bestbefore' => isset($bag['best_before'])
                        ? Carbon::parse($bag['best_before'])->toDateString()
                        : now()->addYears(2)->toDateString(),
                ]);
            }

            return $delivery;
        });

        $certificate = app(CertificateSnapshotter::class)->snapshotFromSupplier($delivery->refresh());

        $bagList = collect($validated['bags'])
            ->map(fn (array $b, int $i): string => "  • {$resolved[$i]->name} — Charge {$b['charge']}, ".Number::kilos($b['size_grams']))
            ->implode("\n");

        $certLine = $certificate !== null
            ? "✓ Zertifikat {$certificate->certificate_number} ({$certificate->bioInspector?->label}) automatisch eingefroren."
            : "⚠ Kein gültiges Zertifikat für {$supplier->shortname} zum {$delivery->delivered_date->format('d.m.Y')}. "
                .'Die Lieferung wurde ohne Zertifikat angelegt; es kann später nachgetragen werden.';

        return Response::text(
            "Created delivery #{$delivery->id} from {$supplier->shortname} on {$delivery->delivered_date->format('d.m.Y')} "
            .'with '.count($validated['bags'])." Gebinde:\n{$bagList}\n\n{$certLine}"
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'supplier' => $schema->string()->description('Supplier shortname, company, or id.')->required(),
            'delivered_date' => $schema->string()->description('Delivery date (YYYY-MM-DD). The certificate valid on this date is frozen onto the delivery.')->required(),
            'bags' => $schema->array()
                ->description('The herb charges delivered. Each item: {herb (name/id), charge (string), size_grams (number), bio (bool, default true), specification (optional), best_before (YYYY-MM-DD, optional)}.')
                ->required(),
        ];
    }
}
