<?php

namespace App\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;

class SparklineColumn extends Column
{
    protected string $view = 'filament.tables.columns.sparkline-column';

    protected int|float|Closure|null $threshold = null;

    public function threshold(int|float|Closure|null $threshold): static
    {
        $this->threshold = $threshold;
        return $this;
    }

    public function getThreshold(mixed $record): ?float
    {
        return $this->evaluate($this->threshold, ['record' => $record]);
    }

    public function getPoints(): string
    {
        $data = $this->getState();

        if (empty($data) || !is_array($data) || count($data) < 2) {
            return '';
        }

        // 1. Normalize Data to 0-100 coordinates
        $max = max($data);
        $min = min($data);
        $delta = $max - $min;
        if ($delta == 0) $delta = 1;

        $width = 100;
        $height = 30;
        $padding = 3; // Keep the line away from the absolute edges

        $rawPoints = [];

        foreach ($data as $index => $value) {
            $x = ($index / (count($data) - 1)) * $width;

            // Normalize Y (Invert because SVG Y=0 is Top)
            // We add padding so the stroke width doesn't get cut off
            $normalizedValue = ($value - $min) / $delta;
            $y = ($height - $padding) - ($normalizedValue * ($height - ($padding * 2)));

            $rawPoints[] = [$x, $y];
        }

        // 2. Smooth the points (Chaikin's Algorithm)
        // Running it twice creates a very pleasing Bezier-like curve
        $smoothedPoints = $this->smoothPoints($rawPoints);
        $smoothedPoints = $this->smoothPoints($smoothedPoints);

        // 3. Convert to SVG string
        return collect($smoothedPoints)
            ->map(fn($p) => implode(',', $p))
            ->implode(' ');
    }

    protected function smoothPoints(array $points): array
    {
        if (count($points) < 3) return $points;

        $newPoints = [];
        $newPoints[] = $points[0]; // Always keep the start point

        // Cut corners: For every pair of points, create 2 new points at 25% and 75% distance
        for ($i = 0; $i < count($points) - 1; $i++) {
            $p0 = $points[$i];
            $p1 = $points[$i + 1];

            $Q = [
                0.75 * $p0[0] + 0.25 * $p1[0],
                0.75 * $p0[1] + 0.25 * $p1[1]
            ];

            $R = [
                0.25 * $p0[0] + 0.75 * $p1[0],
                0.25 * $p0[1] + 0.75 * $p1[1]
            ];

            $newPoints[] = $Q;
            $newPoints[] = $R;
        }

        $newPoints[] = end($points); // Always keep the end point
        return $newPoints;
    }

    public function getInteractivePoints(): array
    {
        $data = $this->getState();

        if (empty($data) || !is_array($data) || count($data) < 2) {
            return [];
        }

        $points = [];
        $count = count($data);
        $width = 100;

        // Divide the 100 unit width by the number of data points
        // to create equal-width hover zones.
        $barWidth = $width / $count;

        foreach (array_values($data) as $index => $value) {
            $points[] = [
                // Position the bar so it roughly lines up with the data point
                'x' => $index * $barWidth,
                'width' => $barWidth,
                'value' => number_format($value, 1, ',', '.') . ' g', // Format the amount
            ];
        }

        return $points;
    }
}
