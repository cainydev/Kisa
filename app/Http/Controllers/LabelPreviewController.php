<?php

namespace App\Http\Controllers;

use App\Labels\TemplateRegistry;
use App\Models\Label;
use App\Services\Labels\LabelRenderer;
use App\Services\Labels\RenderOptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LabelPreviewController extends Controller
{
    public function __invoke(Request $request, Label $label, string $page): Response|BinaryFileResponse
    {
        $registry = app(TemplateRegistry::class);
        if (! $registry->has($label->template_key)) {
            throw new NotFoundHttpException("Unknown template '{$label->template_key}'");
        }
        $template = $registry->get($label->template_key);
        if (! array_key_exists($page, $template->pages())) {
            throw new NotFoundHttpException("Template '{$label->template_key}' has no page '{$page}'");
        }

        $entity = $label->labelable;

        $opts = new RenderOptions(
            bleed_mm: 0,
            marks: false,
            cmyk: false,
        );

        $renderer = app(LabelRenderer::class);
        $format = strtolower((string) $request->query('format', 'png'));

        if ($format === 'pdf') {
            try {
                $path = $renderer->renderPagePdf($template, $page, $label, $entity, $opts);
            } catch (\Throwable $e) {
                $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

                return new Response(
                    "<!doctype html><meta charset=utf-8><body style='font-family:system-ui;padding:1rem;color:#7a1f1f'><h3>Vorschau-Fehler</h3><pre>{$msg}</pre></body>",
                    500,
                    ['Content-Type' => 'text/html; charset=utf-8'],
                );
            }

            return response()
                ->file($path, [
                    'Content-Type' => 'application/pdf',
                    'Cache-Control' => 'no-store, must-revalidate',
                ])
                ->deleteFileAfterSend();
        }

        if ($format === 'html') {
            try {
                $html = $renderer->renderPageHtml($template, $page, $label, $entity, $opts);
            } catch (\Throwable $e) {
                $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                $html = <<<HTML
<!doctype html>
<html lang="de"><head><meta charset="utf-8"><title>Vorschau-Fehler</title>
<style>body{font-family:system-ui,sans-serif;padding:2rem;color:#7a1f1f;background:#fff7f7;}</style>
</head><body><h2>Vorschau-Fehler</h2><pre>{$msg}</pre></body></html>
HTML;
            }

            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'no-store, must-revalidate',
            ]);
        }

        try {
            $path = $renderer->renderPagePng($template, $page, $label, $entity, $opts);
        } catch (\Throwable $e) {
            // Render a small inline error PNG via plain HTML fallback.
            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

            return new Response(
                "<!doctype html><meta charset=utf-8><body style='font-family:system-ui;padding:1rem;color:#7a1f1f'><h3>Vorschau-Fehler</h3><pre>{$msg}</pre></body>",
                500,
                ['Content-Type' => 'text/html; charset=utf-8'],
            );
        }

        return response()
            ->file($path, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'no-store, must-revalidate',
            ])
            ->deleteFileAfterSend();
    }
}
