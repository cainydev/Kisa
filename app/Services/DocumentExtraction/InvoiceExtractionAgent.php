<?php

namespace App\Services\DocumentExtraction;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Extracts header fields from a German invoice (Rechnung).
 *
 * Invoice layouts vary by supplier, so this targets only the reliable header
 * fields shared across the delivery workflow (supplier + date).
 */
class InvoiceExtractionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You extract header information from German invoices ("Rechnung").

        Extract:
        - supplier_name: the issuing company (Rechnungssteller / Absender / Verkäufer),
          the company name only.
        - invoice_date: the invoice date (Rechnungsdatum), ISO 8601 (YYYY-MM-DD).
          German source dates are DD.MM.YYYY.
        - invoice_number: the invoice number (Rechnungs-Nr.) if present.

        Rules:
        - If a field is not present or not legible, return null. Never invent a value.
        - Extract literal values; do not translate company names.
        PROMPT;
    }

    public function messages(): iterable
    {
        return [];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'supplier_name' => $schema->string()->nullable()
                ->description('The issuing company (Rechnungssteller / Verkäufer), name only.'),
            'invoice_date' => $schema->string()->format('date')->nullable()
                ->description('Invoice date (Rechnungsdatum), ISO 8601 (YYYY-MM-DD).'),
            'invoice_number' => $schema->string()->nullable()
                ->description('Invoice number (Rechnungs-Nr.), if present.'),
        ];
    }
}
