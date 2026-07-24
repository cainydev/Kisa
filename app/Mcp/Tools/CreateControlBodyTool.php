<?php

namespace App\Mcp\Tools;

use App\Enums\Country;
use App\Models\BioInspector;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new organic control body (Öko-Kontrollstelle), identified by its öko-code such as "DE-ÖKO-013".')]
class CreateControlBodyTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'oeko_code' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'country' => ['required', 'string', 'size:2', Rule::enum(Country::class)],
        ], [
            'oeko_code.required' => 'Provide the öko-code of the control body (e.g. "DE-ÖKO-013").',
            'company.required' => 'Provide the full legal name of the control body (e.g. "QC&I GmbH").',
            'country.required' => 'Provide the ISO 3166-1 alpha-2 country code of the control body (e.g. "DE").',
        ]);

        $code = trim($validated['oeko_code']);

        $existing = BioInspector::query()->get()->first(
            fn (BioInspector $b): bool => mb_strtolower((string) $b->label) === mb_strtolower($code)
        );

        if ($existing !== null) {
            return Response::error(
                "A control body with öko-code \"{$code}\" already exists (#{$existing->id}: {$existing->company})."
            );
        }

        $inspector = BioInspector::create([
            'label' => $code,
            'company' => trim($validated['company']),
            'country' => Country::from(mb_strtoupper($validated['country'])),
        ]);

        return Response::text(
            "Created control body #{$inspector->id}: {$inspector->label} — {$inspector->company} "
            ."({$inspector->country->getLabel()})."
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'oeko_code' => $schema->string()->description('Öko-code of the control body, e.g. "DE-ÖKO-013".')->required(),
            'company' => $schema->string()->description('Full legal name of the control body.')->required(),
            'country' => $schema->string()->description('ISO 3166-1 alpha-2 country code, e.g. "DE".')->required(),
        ];
    }
}
