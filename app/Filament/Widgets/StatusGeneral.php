<?php

namespace App\Filament\Widgets;

use Throwable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class StatusGeneral extends BaseWidget
{
    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        // Database
        $dbDriver = config('database.default');
        $dbDriverLabel = ucfirst($dbDriver);
        $dbIconMap = [
            'mysql' => 'heroicon-m-table-cells',
            'pgsql' => 'heroicon-m-table-cells',
            'sqlite' => 'heroicon-m-square-3-stack-3d',
            'sqlsrv' => 'heroicon-m-server-stack',
            'mariadb' => 'heroicon-m-table-cells',
        ];
        $dbIcon = $dbIconMap[$dbDriver] ?? 'heroicon-m-database';

        try {
            DB::connection()->getPdo();
            $dbStatus = 'Connected';
            $dbColor = 'success';
            $dbStatusIcon = 'heroicon-m-check-circle';
        } catch (Throwable $e) {
            $dbStatus = 'Error';
            $dbColor = 'danger';
            $dbStatusIcon = 'heroicon-m-x-circle';
        }

        // Queue
        $queueDriver = config('queue.default');
        $queueDriverLabel = ucfirst($queueDriver);
        $queueIconMap = [
            'redis' => 'heroicon-m-bolt',
            'database' => 'heroicon-m-table-cells',
            'sync' => 'heroicon-m-arrow-path',
            'sqs' => 'heroicon-m-cloud',
            'beanstalkd' => 'heroicon-m-arrow-trending-up',
        ];
        $queueIcon = $queueIconMap[$queueDriver] ?? 'heroicon-m-bolt';

        try {
            $queueSize = Queue::size();
            $queueDesc = $queueSize > 0 ? "$queueSize pending" : "Idle";
            $queueColor = $queueSize > 10 ? 'warning' : 'success';
            $queueStatusIcon = $queueSize > 10 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle';
        } catch (Throwable $e) {
            $queueDesc = 'Unavailable';
            $queueColor = 'danger';
            $queueStatusIcon = 'heroicon-m-x-circle';
        }

        // Cache
        $cacheDriver = config('cache.default');
        $cacheDriverLabel = ucfirst($cacheDriver);
        $cacheIconMap = [
            'redis' => 'heroicon-m-bolt',
            'memcached' => 'heroicon-m-light-bulb',
            'file' => 'heroicon-m-document',
            'array' => 'heroicon-m-table-cells',
            'database' => 'heroicon-m-table-cells',
        ];
        $cacheIcon = $cacheIconMap[$cacheDriver] ?? 'heroicon-m-bolt';

        // Try a cache stat: can we read/write?
        $cacheTestKey = '__filament_health_cache_test';
        try {
            Cache::put($cacheTestKey, 'ok', 10);
            $cacheStatus = Cache::get($cacheTestKey) === 'ok' ? 'Working' : 'Problem';
            $cacheColor = $cacheStatus === 'Working' ? 'success' : 'danger';
            $cacheStatusIcon = $cacheStatus === 'Working' ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle';
        } catch (Throwable $e) {
            $cacheStatus = 'Error';
            $cacheColor = 'danger';
            $cacheStatusIcon = 'heroicon-m-x-circle';
        }

        return [
            Stat::make('Database', $dbStatus)
                ->description("{$dbDriverLabel} driver")
                ->descriptionIcon($dbStatusIcon)
                ->icon($dbIcon)
                ->color($dbColor),

            Stat::make('Queue', $queueDesc)
                ->description("{$queueDriverLabel} driver")
                ->descriptionIcon($queueStatusIcon)
                ->icon($queueIcon)
                ->color($queueColor),

            Stat::make('Cache', $cacheStatus)
                ->description("{$cacheDriverLabel} driver")
                ->descriptionIcon($cacheStatusIcon)
                ->icon($cacheIcon)
                ->color($cacheColor),
        ];
    }
}
