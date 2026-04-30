<?php

namespace App\Services\Labels;

use RuntimeException;
use Symfony\Component\Process\Process;

class CmykConverter
{
    /**
     * Finalize a Browsershot-generated PDF for print:
     *   - Use Ghostscript to (optionally) convert RGB → CMYK while preserving vectors.
     *   - Use pdfcpu to set authoritative MediaBox / TrimBox / BleedBox in mm.
     *
     * Browsershot's mm→pt conversion drifts by up to ~1pt. Setting the boxes
     * explicitly afterward gives the printer reliable cut/bleed dimensions
     * regardless of any drift in the source file.
     *
     * @param  string  $inPath  Input PDF path.
     * @param  float  $trimWidthMm  Final cut size width in mm.
     * @param  float  $trimHeightMm  Final cut size height in mm.
     * @param  float  $bleedMm  Bleed depth in mm (0 if no bleed).
     * @param  float  $marginToTrimMm  Distance from sheet edge to trim line in mm.
     *                                 Equals bleed plus crop-mark area if marks are on.
     * @param  bool  $cmyk  Whether to convert RGB to CMYK.
     * @return string Path to the finalized PDF (different from input).
     */
    public function convert(
        string $inPath,
        float $trimWidthMm,
        float $trimHeightMm,
        float $bleedMm = 0,
        float $marginToTrimMm = 0,
        bool $cmyk = true,
    ): string {
        if (! is_file($inPath)) {
            throw new RuntimeException("Input PDF not found: {$inPath}");
        }

        $suffix = $cmyk ? '-cmyk.pdf' : '-final.pdf';
        $outPath = preg_replace('/\.pdf$/i', $suffix, $inPath);
        if ($outPath === $inPath) {
            $outPath = $inPath.$suffix;
        }

        // Step 1: Ghostscript pass for color-space conversion (and to fix any
        // non-vector content). If CMYK is off, we still run gs to normalize the
        // page-size drift Browsershot introduces. The output goes to a temp file
        // that pdfcpu will then take and stamp boxes onto.
        $intermediatePath = $inPath.'.gs.pdf';

        $cmd = [
            $this->gsBinary(),
            '-dBATCH',
            '-dNOPAUSE',
            '-dNOOUTERSAVE',
            '-dSAFER',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.6',
            // Set exact page size in pt — fixes Browsershot's mm→pt drift.
            '-dDEVICEWIDTHPOINTS='.number_format($this->mmToPt($trimWidthMm + 2 * $marginToTrimMm), 4, '.', ''),
            '-dDEVICEHEIGHTPOINTS='.number_format($this->mmToPt($trimHeightMm + 2 * $marginToTrimMm), 4, '.', ''),
            '-dFIXEDMEDIA',
            // Preserve vector content — never rasterize text/paths/transparency.
            '-dEncodeColorImages=true',
            '-dEncodeGrayImages=true',
            '-dEncodeMonoImages=true',
            '-dDownsampleColorImages=false',
            '-dDownsampleGrayImages=false',
            '-dDownsampleMonoImages=false',
            '-dAutoFilterColorImages=false',
            '-dAutoFilterGrayImages=false',
            '-dColorImageFilter=/FlateEncode',
            '-dGrayImageFilter=/FlateEncode',
            // Keep fonts embedded as vector outlines.
            '-dEmbedAllFonts=true',
            '-dSubsetFonts=true',
            '-dCompressFonts=true',
            // Don't flatten transparency to a raster fallback.
            '-dHaveTransparency=true',
            '-dPreserveOverprintSettings=true',
        ];

        if ($cmyk) {
            $iccPath = resource_path('print/icc/ISOcoated_v2_300_eci.icc');
            $cmd[] = '-sColorConversionStrategy=CMYK';
            $cmd[] = '-sProcessColorModel=DeviceCMYK';
            $cmd[] = '-dOverrideICC=true';
            if (is_file($iccPath)) {
                $cmd[] = '-sOutputICCProfile='.$iccPath;
            }
        }

        $cmd[] = '-sOutputFile='.$intermediatePath;
        $cmd[] = $inPath;

        $process = new Process($cmd);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($intermediatePath)) {
            throw new RuntimeException(
                'Ghostscript conversion failed: '.($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        // Step 2: pdfcpu sets the page boxes in mm. Trim is the final cut area,
        // centered on the sheet. Bleed is trim outset by $bleedMm.
        $sheetW = $trimWidthMm + 2 * $marginToTrimMm;
        $sheetH = $trimHeightMm + 2 * $marginToTrimMm;
        $trimL = $marginToTrimMm;
        $trimT = $marginToTrimMm;
        $trimR = $marginToTrimMm + $trimWidthMm;
        $trimB = $marginToTrimMm + $trimHeightMm;
        $bleedL = $marginToTrimMm - $bleedMm;
        $bleedT = $marginToTrimMm - $bleedMm;
        $bleedR = $sheetW - ($marginToTrimMm - $bleedMm);
        $bleedB = $sheetH - ($marginToTrimMm - $bleedMm);

        $description = sprintf(
            'media:[0 0 %s %s], crop:[0 0 %s %s], bleed:[%s %s %s %s], trim:[%s %s %s %s], art:[%s %s %s %s]',
            $this->fmt($sheetW), $this->fmt($sheetH),
            $this->fmt($sheetW), $this->fmt($sheetH),
            $this->fmt($bleedL), $this->fmt($bleedT), $this->fmt($bleedR), $this->fmt($bleedB),
            $this->fmt($trimL), $this->fmt($trimT), $this->fmt($trimR), $this->fmt($trimB),
            $this->fmt($trimL), $this->fmt($trimT), $this->fmt($trimR), $this->fmt($trimB),
        );

        $boxProcess = new Process([
            $this->pdfcpuBinary(),
            'boxes',
            'add',
            '-u', 'mm',
            '-q',
            $description,
            $intermediatePath,
            $outPath,
        ]);
        $boxProcess->setTimeout(60);
        $boxProcess->run();

        if (! $boxProcess->isSuccessful() || ! is_file($outPath)) {
            throw new RuntimeException(
                'pdfcpu box-stamping failed: '.($boxProcess->getErrorOutput() ?: $boxProcess->getOutput())
            );
        }

        @unlink($inPath);
        @unlink($intermediatePath);

        return $outPath;
    }

    private function mmToPt(float $mm): float
    {
        return $mm * 72.0 / 25.4;
    }

    private function fmt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function gsBinary(): string
    {
        return env('GHOSTSCRIPT_BINARY', 'gs');
    }

    private function pdfcpuBinary(): string
    {
        return env('PDFCPU_BINARY', 'pdfcpu');
    }
}
