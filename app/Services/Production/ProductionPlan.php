<?php

namespace App\Services\Production;

use Carbon\Carbon;

/**
 * The inputs that shape a production plan: how far back to look for open
 * orders, how far to project future demand, and how to round batch sizes.
 */
readonly class ProductionPlan
{
    public function __construct(
        public Carbon $since,
        public int $extrapolateMonths,
        public int $extrapolateMaxSize,
        public int $roundUpTo = 0,
    ) {}

    /**
     * Round a quantity up to the configured batch multiple (no-op when 0).
     */
    public function round(int $quantity): int
    {
        if ($this->roundUpTo <= 0) {
            return $quantity;
        }

        return $this->roundUpTo * (int) ceil($quantity / $this->roundUpTo);
    }
}
