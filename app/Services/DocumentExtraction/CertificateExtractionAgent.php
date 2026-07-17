<?php

namespace App\Services\DocumentExtraction;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Extracts the structured fields of an EU organic certificate
 * (Verordnung (EU) 2018/848) from an uploaded PDF.
 */
class CertificateExtractionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You extract structured data from EU organic-farming certificates
        ("Zertifikat gemäß Artikel 35 der Verordnung (EU) 2018/848").

        These certificates follow a fixed numbered layout. Map the fields as follows:
        - Field 1 "Nummer des Zertifikats" -> certificate_number
        - Field 3 "Name und Anschrift des Unternehmers" -> operator_name (the company name only, not the address)
        - Field 4 "Kontrollstelle" -> control_body (the body's name, e.g. "ABCERT AG") and
          control_body_code (the DE-ÖKO-### code, e.g. "DE-ÖKO-006")
        - Field 5 "Tätigkeit(en)" -> activities (comma-separated if multiple, e.g. "Aufbereitung, Einfuhr")
        - Field 6 "Erzeugniskategorie(n)" -> product_categories (the category text)
        - Field 7 "Ort, Datum" -> issued_place (the place) and issued_at (the date)
        - Field 8 "Zertifikat gültig vom" -> valid_from and valid_until (the two dates of the validity range)

        Rules:
        - Return dates in ISO 8601 format (YYYY-MM-DD). German source dates are DD.MM.YYYY.
        - If a field is not present or not legible, return null for it. Never invent a value.
        - The document may be in German; extract the literal values, do not translate names.
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
            'certificate_number' => $schema->string()->nullable()
                ->description('Field 1: "Nummer des Zertifikats".'),
            'operator_name' => $schema->string()->nullable()
                ->description('Field 3: certified operator company name (no address).'),
            'control_body' => $schema->string()->nullable()
                ->description('Field 4: control body name, e.g. "ABCERT AG".'),
            'control_body_code' => $schema->string()->nullable()
                ->description('Field 4: the DE-ÖKO-### control body code.'),
            'activities' => $schema->string()->nullable()
                ->description('Field 5: activities, comma-separated if multiple.'),
            'product_categories' => $schema->string()->nullable()
                ->description('Field 6: product categories text.'),
            'valid_from' => $schema->string()->format('date')->nullable()
                ->description('Field 8: start of validity, ISO 8601 (YYYY-MM-DD).'),
            'valid_until' => $schema->string()->format('date')->nullable()
                ->description('Field 8: end of validity, ISO 8601 (YYYY-MM-DD).'),
            'issued_at' => $schema->string()->format('date')->nullable()
                ->description('Field 7: date of issue, ISO 8601 (YYYY-MM-DD).'),
            'issued_place' => $schema->string()->nullable()
                ->description('Field 7: place of issue.'),
        ];
    }
}
