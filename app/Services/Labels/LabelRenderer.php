<?php

namespace App\Services\Labels;

use App\Labels\LabelTemplate;
use App\Labels\ParameterResolver;
use App\Labels\TemplateRegistry;
use App\Models\Label;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use Spatie\Browsershot\Browsershot;

class LabelRenderer
{
    /**
     * Set after renderPagePdf when RenderOptions::checkOverflow was true.
     * Null otherwise (no check was performed). True when the rendered page
     * has visible content that exceeds its container; false when it fits.
     */
    public ?bool $lastOverflow = null;

    public function __construct(
        private readonly TemplateRegistry $registry,
        private readonly ParameterResolver $resolver,
    ) {}

    /**
     * Render a single named page to HTML.
     */
    public function renderPageHtml(
        LabelTemplate $template,
        string $pageKey,
        ?Label $label,
        ?Model $entity,
        RenderOptions $opts,
    ): string {
        $pages = $template->pages();
        if (! isset($pages[$pageKey])) {
            throw new InvalidArgumentException(
                "Template '{$template->key()}' has no page '{$pageKey}'. Pages: ".implode(',', array_keys($pages))
            );
        }

        $values = $this->resolver->resolve($template, $label, $entity);
        $dims = $template->dimensions();
        $values['width'] = $dims['width_mm'];
        $values['height'] = $dims['height_mm'];
        $values['bleed'] = $opts->bleed_mm;
        $values['marks'] = $opts->marks;
        $values['slug'] = $this->slugFor($template, $pageKey, $label, $entity);
        $values['entity'] = $entity;

        $body = View::make($pages[$pageKey], $values)->render();

        $title = htmlspecialchars($template->name().' / '.$pageKey, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>{$title}</title>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }

    /**
     * Render a single named page to a single-page PDF. Returns the file path.
     */
    public function renderPagePdf(
        LabelTemplate $template,
        string $pageKey,
        ?Label $label,
        ?Model $entity,
        RenderOptions $opts,
    ): string {
        $this->lastOverflow = null;

        $html = $this->renderPageHtml($template, $pageKey, $label, $entity, $opts);

        $dir = storage_path('app/labels/tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/'.uniqid("label-{$template->key()}-{$pageKey}-", true).'.pdf';

        $dims = $template->dimensions();
        // Margin from sheet edge to trim. Includes bleed plus crop-mark area
        // when marks are enabled. Must match the chassis component.
        $markLen = 5; // mm — keep in sync with resources/views/components/label-page.blade.php
        $marginToTrim = $opts->bleed_mm + ($opts->marks ? $markLen : 0);
        $pageW = $dims['width_mm'] + 2 * $marginToTrim;
        $pageH = $dims['height_mm'] + 2 * $marginToTrim;

        $shot = Browsershot::html($html)
            ->paperSize($pageW, $pageH, 'mm')
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

        $shot->save($path);

        if ($opts->checkOverflow) {
            $this->lastOverflow = $this->detectOverflow($html);
        }

        return $path;
    }

    /**
     * Run an evaluate-only Browsershot call to determine whether the rendered
     * HTML overflows its trim box. The probe is injected by the chassis
     * component (resources/views/components/label-page.blade.php) which sets
     * window.__labelOverflow once fonts are loaded.
     *
     * Adds ~600ms to the PDF generation but only runs when explicitly opted-in
     * via RenderOptions::$checkOverflow — the print path does, the preview
     * path does not.
     */
    private function detectOverflow(string $html): ?bool
    {
        $shot = Browsershot::html($html)
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

        try {
            // Wait for the chassis probe to land its result on window, then
            // return it. Falls back to recomputing inline if the probe hasn't
            // run for any reason (e.g. font loading hung).
            $raw = $shot->evaluate(<<<'JS'
                (async () => {
                    if (document.fonts && document.fonts.ready) {
                        try { await document.fonts.ready; } catch (e) {}
                    }
                    if (typeof window.__labelOverflow !== 'undefined') {
                        return window.__labelOverflow ? '1' : '0';
                    }
                    var TOL = 1;
                    if (document.body.scrollHeight > document.documentElement.clientHeight + TOL) return '1';
                    var nodes = document.querySelectorAll('*');
                    for (var i = 0; i < nodes.length; i++) {
                        var el = nodes[i];
                        if (el.scrollHeight > el.clientHeight + TOL) return '1';
                        if (el.scrollWidth > el.clientWidth + TOL) return '1';
                    }
                    return '0';
                })();
            JS);

            return trim($raw) === '1';
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Render a single named page to a PNG image. Returns the file path.
     *
     * Used for the live preview. Renders at 2× device-pixel ratio for sharp display.
     */
    public function renderPagePng(
        LabelTemplate $template,
        string $pageKey,
        ?Label $label,
        ?Model $entity,
        RenderOptions $opts,
    ): string {
        $html = $this->renderPageHtml($template, $pageKey, $label, $entity, $opts);

        $dir = storage_path('app/labels/preview');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/'.uniqid("label-{$template->key()}-{$pageKey}-", true).'.png';

        $dims = $template->dimensions();
        // 1mm ≈ 3.78px at 96dpi. Width/height in mm × 3.78 = browser px.
        $pageW = (int) round(($dims['width_mm'] + 2 * $opts->bleed_mm) * 3.78);
        $pageH = (int) round(($dims['height_mm'] + 2 * $opts->bleed_mm) * 3.78);

        $shot = Browsershot::html($html)
            ->windowSize($pageW, $pageH)
            ->deviceScaleFactor(2)
            ->fullPage()
            ->showBackground()
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

        $shot->save($path);

        return $path;
    }

    /**
     * Build the print-shop slug shown in the slug area when crop marks are on.
     * Format: "<label-or-template-name> · <pageKey> · <YYYY-MM-DD HH:MM>"
     */
    private function slugFor(LabelTemplate $template, string $pageKey, ?Label $label, ?Model $entity): string
    {
        $base = $label?->name
            ?: ($entity?->name ?? $template->name());

        return sprintf('%s · %s · %s', $base, $pageKey, now()->format('Y-m-d H:i'));
    }
}
