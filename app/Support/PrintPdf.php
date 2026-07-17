<?php

namespace App\Support;

use Spatie\Browsershot\Browsershot;

/**
 * Renders a print Blade view to an A4 PDF via Browsershot — the same headless
 * Chromium the label engine uses. Shared by the observability pages so their
 * "Drucken" actions can generate and stream a PDF without a separate route.
 */
class PrintPdf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromView(string $view, array $data): string
    {
        $html = view($view, $data)->render();

        // Margins are owned by the document's CSS @page rule so the HTML preview
        // and the PDF match exactly; Browsershot adds none of its own.
        $shot = Browsershot::html($html)
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->emulateMedia('print')
            ->waitUntilNetworkIdle()
            ->noSandbox();

        if ($chromePath = config('labels.browsershot.chromium_path')) {
            $shot->setChromePath($chromePath);
        }
        if ($nodeBinary = config('labels.browsershot.node_binary')) {
            $shot->setNodeBinary($nodeBinary);
        }
        if ($npmBinary = config('labels.browsershot.npm_binary')) {
            $shot->setNpmBinary($npmBinary);
        }

        return $shot->pdf();
    }
}
