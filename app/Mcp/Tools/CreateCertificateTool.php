<?php

namespace App\Mcp\Tools;

use App\Enums\CertificateActivity;
use App\Enums\ProductCategory;
use App\Mcp\Concerns\ResolvesEntities;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create an organic certificate for a supplier. The control body is given by its öko-code (e.g. "DE-ÖKO-001"), which must exist. Activities and product categories use the EU 2018/848 vocabularies. Once created, deliveries in the validity window resolve this certificate automatically.')]
class CreateCertificateTool extends Tool
{
    use ResolvesEntities;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'supplier' => 'required|string',
            'oeko_code' => 'required|string',
            'certificate_number' => 'required|string|max:255',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'issued_at' => 'nullable|date',
            'issued_place' => 'nullable|string|max:255',
            'activities' => 'nullable|array',
            'activities.*' => 'string',
            'product_categories' => 'nullable|array',
            'product_categories.*' => 'string',
        ], [
            'supplier.required' => 'Provide the supplier (shortname, company, or id).',
            'oeko_code.required' => 'Provide the control body öko-code, e.g. "DE-ÖKO-001".',
            'certificate_number.required' => 'Provide the certificate number as printed on the document.',
            'valid_until.after' => 'valid_until must be after valid_from.',
        ]);

        $supplier = $this->resolveSupplier($validated['supplier']);
        if ($supplier === null) {
            return Response::error("No supplier found matching \"{$validated['supplier']}\".");
        }

        $inspector = $this->resolveBioInspector($validated['oeko_code']);
        if ($inspector === null) {
            return Response::error("No control body found for öko-code \"{$validated['oeko_code']}\". It must exist as a BioInspector first.");
        }

        $activities = $this->mapEnum($validated['activities'] ?? [], CertificateActivity::class);
        if ($activities === false) {
            $valid = collect(CertificateActivity::cases())->map->value->implode(', ');

            return Response::error("Invalid activity. Allowed values: {$valid}.");
        }

        $categories = $this->mapEnum($validated['product_categories'] ?? [], ProductCategory::class);
        if ($categories === false) {
            $valid = collect(ProductCategory::cases())->map(fn ($c) => $c->value.' = '.$c->getLabel())->implode('; ');

            return Response::error("Invalid product category. Allowed values: {$valid}.");
        }

        $certificate = $supplier->certificates()->create([
            'bio_inspector_id' => $inspector->id,
            'certificate_number' => $validated['certificate_number'],
            'valid_from' => Carbon::parse($validated['valid_from'])->toDateString(),
            'valid_until' => Carbon::parse($validated['valid_until'])->toDateString(),
            'issued_at' => isset($validated['issued_at']) ? Carbon::parse($validated['issued_at'])->toDateString() : null,
            'issued_place' => $validated['issued_place'] ?? null,
            'activities' => $activities,
            'product_categories' => $categories,
        ]);

        return Response::text(
            "Created certificate #{$certificate->id} {$certificate->certificate_number} "
            ."for {$supplier->shortname}, control body {$inspector->label}, "
            .'valid '.$certificate->valid_from->format('d.m.Y').' – '.$certificate->valid_until->format('d.m.Y').'.'
        );
    }

    /**
     * Map a list of enum backing values to enum cases; false if any is invalid.
     *
     * @param  array<int, string>  $values
     * @param  class-string  $enum
     * @return array<int, object>|false
     */
    private function mapEnum(array $values, string $enum): array|false
    {
        $mapped = [];
        foreach ($values as $value) {
            $case = $enum::tryFrom($value);
            if ($case === null) {
                return false;
            }
            $mapped[] = $case;
        }

        return $mapped;
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'supplier' => $schema->string()->description('Supplier shortname, company, or id.')->required(),
            'oeko_code' => $schema->string()->description('Control body öko-code, e.g. "DE-ÖKO-001".')->required(),
            'certificate_number' => $schema->string()->description('Certificate number as printed on the document.')->required(),
            'valid_from' => $schema->string()->description('Validity start date (YYYY-MM-DD).')->required(),
            'valid_until' => $schema->string()->description('Validity end date (YYYY-MM-DD).')->required(),
            'issued_at' => $schema->string()->description('Issue date (YYYY-MM-DD). Used to pick the newest certificate when several cover a date.'),
            'issued_place' => $schema->string()->description('Place of issue (optional).'),
            'activities' => $schema->array()->description('EU activities, e.g. ["Aufbereitung","Einfuhr","Ausfuhr","Inverkehrbringen","Erzeugung","Kennzeichnung","Lagerung"].'),
            'product_categories' => $schema->array()->description('EU product category letters, e.g. ["a","d"] (a–g per Reg. 2018/848).'),
        ];
    }
}
