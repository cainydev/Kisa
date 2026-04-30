<?php

namespace App\Services\Labels;

final readonly class RenderOptions
{
    public function __construct(
        public int $bleed_mm = 3,
        public bool $marks = true,
        public bool $cmyk = false,
    ) {}
}
