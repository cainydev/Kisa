<?php

namespace App\Support\Stats;

/**
 * Grams of one herb already promised to customers: sold products that still
 * have to be bottled. Broken down per product so the table can explain where
 * the number comes from.
 */
final readonly class HerbReservation
{
    /**
     * @param  list<array{variant: string, missing: int, grams: float}>  $lines
     */
    public function __construct(
        public float $grams = 0,
        public array $lines = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->grams <= 0;
    }
}
