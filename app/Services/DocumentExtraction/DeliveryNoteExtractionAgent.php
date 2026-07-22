<?php

namespace App\Services\DocumentExtraction;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Extracts the header and line items (positions) from a German delivery note
 * (Lieferschein). The header pre-fills the delivery form; the positions are
 * proposed in a review modal from which the user creates the Bag records.
 */
class DeliveryNoteExtractionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You extract data from German delivery notes ("Lieferschein"). These
        identify the supplier and the delivery date, and list the delivered
        goods as line items (Positionen).

        Header:
        - supplier_name: the sending company (Lieferant / Absender), name only.
        - delivered_date: the delivery/shipping date (Lieferdatum), ISO 8601 (YYYY-MM-DD).
          German source dates are DD.MM.YYYY.
        - delivery_note_number: the delivery note number (Lieferschein-Nr.), if present.

        Positions — one entry per delivered line item:
        - herb_name: the raw material / herb name as printed (e.g. "Anis", "Baldrianwurzel").
        - specification: any quality/processing spec (e.g. "BIO geschnitten", "ganz"), if present.
        - charge: the manufacturer's batch/lot number (Charge / Chargen-Nr.).
        - size_grams: the quantity of this line converted to GRAMS as an integer
          (1 kg = 1000 g). If the note lists kg, multiply by 1000.
        - best_before: best-before date (MHD / mindestens haltbar bis), ISO 8601, if present.
        - bio: true if the line is marked organic/BIO, otherwise false.

        Rules:
        - If a field is not present or not legible, return null (bio defaults to false).
          Never invent a value.
        - Extract literal values; do not translate names.
        - Include every delivered line item as a separate position entry.
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
            'positions' => $schema->array()
                ->description('One entry per delivered line item.')
                ->items($schema->object([
                    'herb_name' => $schema->string()->nullable()
                        ->description('Raw material / herb name as printed.'),
                    'specification' => $schema->string()->nullable()
                        ->description('Quality/processing spec, if present.'),
                    'charge' => $schema->string()->nullable()
                        ->description('Manufacturer batch/lot number (Charge).'),
                    'size_grams' => $schema->integer()->nullable()
                        ->description('Line quantity in grams (kg × 1000).'),
                    'best_before' => $schema->string()->format('date')->nullable()
                        ->description('Best-before date (MHD), ISO 8601.'),
                    'bio' => $schema->boolean()->nullable()
                        ->description('True if the line is marked organic/BIO.'),
                ])),
        ];
    }
}
