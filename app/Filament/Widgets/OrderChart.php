<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrderChart extends ChartWidget
{
    protected ?string $heading = 'Bestellungen pro Tag';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = 14;
        $orders = Order::query()
            ->where('date', '>=', now()->subDays($days))
            ->get()
            ->groupBy(fn($order) => Carbon::parse($order->date)->format('d.m.Y'));

        $labels = collect(range(0, $days - 1))
            ->map(fn($i) => now()->subDays($days - 1 - $i)->format('d.m.Y'));

        $data = $labels->map(fn($date) => $orders->get($date, collect())->count());

        return [
            'datasets' => [
                [
                    'label' => 'Bestellungen',
                    'data' => $data,
                    'borderColor' => '#6d8b5c',
                    'borderWidth' => 4,
                    'fill' => false,
                    'tension' => 1,
                    'cubicInterpolationMode' => 'monotone',
                ],
            ],
            'labels' => $labels->toArray(),
            'options' => [
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'x' => [
                        'display' => false,
                    ],
                    'y' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
