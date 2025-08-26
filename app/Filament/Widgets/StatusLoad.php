<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class StatusLoad extends ChartWidget
{
    protected ?string $pollingInterval = '5s';
    protected ?string $heading = 'CPU & RAM Usage';
    protected ?string $maxHeight = '280px';
    protected ?array $options = [
        'animation' => [
            'duration' => 600,
            'easing' => 'easeOutQuart',
        ],
        'plugins' => [
            'legend' => [
                'display' => true,
                'labels' => [
                    'color' => '#64748b',
                    'font' => [
                        'weight' => 'bold',
                    ],
                ],
            ],
            'tooltip' => [
                'enabled' => false,
                'mode' => 'nearest',
                'intersect' => false,
                'backgroundColor' => '#0f172a',
                'borderColor' => '#6366f1',
                'borderWidth' => 1,
            ],
        ],
        'scales' => [
            'y' => [
                'ticks' => [
                    'stepSize' => 5, // 5% per step
                    'color' => '#334155',
                    'font' => [
                        'weight' => 'bold',
                        'size' => 16,
                    ],
                    'padding' => 16,
                ],
            ],
            'x' => [
                'display' => false,
                'ticks' => [
                    'display' => false,
                ],
                'grid' => [
                    'display' => false,
                ],
            ],
        ],
        'elements' => [
            'line' => [
                'borderJoinStyle' => 'round'
            ],
        ],
    ];

    protected int $maxSamples = 60;
    protected string $cacheKey = 'filament-cpu-ram-samples';

    protected function getData(): array
    {
        $samples = Cache::get($this->cacheKey, []);

        // RAM usage
        $meminfo = @file_get_contents('/proc/meminfo');
        $memTotal = 1;
        $memAvailable = 1;
        if ($meminfo) {
            if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $m)) {
                $memTotal = (int)$m[1];
            }
            if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $m)) {
                $memAvailable = (int)$m[1];
            }
        }
        $ramUsedPercent = 100 - round($memAvailable * 100 / max(1, $memTotal), 2);

        // CPU usage
        $cores = (int)shell_exec('nproc') ?: 1;
        $load = sys_getloadavg()[0] ?? 0;
        $cpuUsedPercent = round(min(100, $load * 100 / max(1, $cores)), 2);

        $samples[] = [
            'cpu' => $cpuUsedPercent,
            'ram' => $ramUsedPercent,
            't' => now()->format('H:i:s'),
        ];

        $samples = array_slice($samples, -$this->maxSamples);
        Cache::put($this->cacheKey, $samples, now()->addMinutes(10));

        return [
            'datasets' => [
                [
                    'label' => 'CPU',
                    'data' => array_column($samples, 'cpu'),
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14,165,233,0.10)',
                    'pointRadius' => 0,
                    'tension' => 0.35,
                    'borderWidth' => 2.5,
                ],
                [
                    'label' => 'RAM',
                    'data' => array_column($samples, 'ram'),
                    'borderColor' => '#f43f5e',
                    'backgroundColor' => 'rgba(244,63,94,0.10)',
                    'pointRadius' => 0,
                    'tension' => 0.35,
                    'borderWidth' => 2.5,
                ],
            ],
            'labels' => array_fill(0, count($samples), ''),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
