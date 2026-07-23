<?php

namespace App\Mcp\Tools;

use App\Models\Supplier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new supplier. The organic control body is not set here — it is derived from the certificates you later add to the supplier.')]
class CreateSupplierTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'company' => 'required|string|max:255',
            'shortname' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
        ], [
            'company.required' => 'Provide the full legal company name (e.g. "Alfred Galke GmbH").',
            'shortname.required' => 'Provide a short internal name (e.g. "Galke").',
            'email.email' => 'The email address is not valid.',
        ]);

        if (Supplier::whereRaw('LOWER(shortname) = ?', [mb_strtolower($validated['shortname'])])->exists()) {
            return Response::error("A supplier with shortname \"{$validated['shortname']}\" already exists.");
        }

        $supplier = Supplier::create($validated);

        return Response::text(
            "Created supplier #{$supplier->id}: {$supplier->company} ({$supplier->shortname}). "
            .'Add an organic certificate with create-certificate to set its control body.'
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Full legal company name.')->required(),
            'shortname' => $schema->string()->description('Short internal name / nickname.')->required(),
            'contact' => $schema->string()->description('Contact person (optional).'),
            'email' => $schema->string()->description('Contact email (optional).'),
            'phone' => $schema->string()->description('Phone number (optional).'),
            'website' => $schema->string()->description('Website (optional).'),
        ];
    }
}
