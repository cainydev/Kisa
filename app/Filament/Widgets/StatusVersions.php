<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatusVersions extends BaseWidget
{
    protected function getStats(): array
    {
        $bytes = @disk_free_space(base_path());
        if ($bytes === false) {
            $diskFree = 'Unknown';
            $diskColor = 'danger';
            $diskIcon = 'heroicon-m-x-circle';
        } else {
            $gb = $bytes / 1024 / 1024 / 1024;
            $diskFree = number_format($gb, 1) . ' GB free';
            $diskColor = $gb < 2 ? 'danger' : ($gb < 10 ? 'warning' : 'success');
            $diskIcon = $gb < 2 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle';
        }

        $ini = php_ini_loaded_file();
        $env = config('app.env');

        // Uptime
        $uptime = 'Unknown';
        if (is_readable('/proc/uptime')) {
            $uptimeSeconds = (int)floatval(file_get_contents('/proc/uptime'));
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            $uptime = ($days ? $days . 'd ' : '') . ($hours ? $hours . 'h ' : '') . $minutes . 'm';
        }

        return [
            Stat::make('Laravel Version', app()->version())
                ->description('Enviroment: ' . $env)
                ->descriptionIcon(in_array($env, ['prod', 'production']) ? 'heroicon-m-check-circle' : 'heroicon-m-information-circle')
                ->color(in_array($env, ['prod', 'production']) ? 'success' : 'gray'),

            Stat::make('PHP Version', PHP_VERSION)
                ->description('Ini: ' . ($ini ?: 'Unknown'))
                ->descriptionIcon($ini ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($ini ? 'success' : 'warning'),

            Stat::make('Disk Space', $diskFree)
                ->description('Available storage')
                ->descriptionIcon($diskIcon)
                ->color($diskColor),

            Stat::make('Uptime', $uptime)
                ->description('Since last boot')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),
        ];
    }
}
