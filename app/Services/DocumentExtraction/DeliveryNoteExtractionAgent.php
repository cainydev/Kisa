<?php

namespace App\Services\DocumentExtraction;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Extracts header fields from a German delivery note (Lieferschein).
 *
 * Delivery-note layouts vary by supplier far more than the standardised EU
 * organic certificate, so this intentionally targets only the reliable header
 * fields. Line items (Gebinde/charges) are left to manual entry for now.
 */
class DeliveryNoteExtractionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You extract header information from German delivery notes ("Lieferschein").
        These documents identify the supplier (Lieferant/Absender) and the date
        the goods were shipped or delivered.

        Extract:
        - supplier_name: the sending company (Lieferant / Absender), the company name only.
        - delivered_date: the delivery or shipping date (Lieferdatum / Lieferschein-Datum),
          in ISO 8601 format (YYYY-MM-DD). German source dates are DD.MM.YYYY.
        - delivery_note_number: the delivery note number (Lieferschein-Nr.) if present.

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
                ->description('The sending company (Lieferant / Absender), name only.'),
            'delivered_date' => $schema->string()->format('date')->nullable()
                ->description('Delivery/shipping date, ISO 8601 (YYYY-MM-DD).'),
            'delivery_note_number' => $schema->string()->nullable()
                ->description('Delivery note number (Lieferschein-Nr.), if present.'),
        ];
    }
}
