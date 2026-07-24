<?php

namespace App\Mcp\Tools;

use App\Models\BioInspector;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List all known organic control bodies (Öko-Kontrollstellen) with their öko-code, company name and country.')]
class ListControlBodiesTool extends Tool
{
    public function handle(Request $request): Response
    {
        $inspectors = BioInspector::query()
            ->withCount('certificates')
            ->orderBy('label')
            ->get();

        if ($inspectors->isEmpty()) {
            return Response::text('No control bodies found.');
        }

        $lines = $inspectors->map(function (BioInspector $b): string {
            $country = $b->country?->getLabel() ?? 'kein Land hinterlegt';

            return "• #{$b->id} {$b->label} — {$b->company} ({$country}), "
                ."{$b->certificates_count} Zertifikat(e)";
        })->implode("\n");

        return Response::text("Control bodies ({$inspectors->count()}):\n{$lines}");
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
