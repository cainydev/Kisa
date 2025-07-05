<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class Status extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.status';
    protected static ?string $navigationLabel = 'Status';

    public function getViewData(): array
    {
        return [
            'appEnv' => App::environment(),
            'laravelVersion' => app()->version(),
            'phpVersion' => phpversion(),
            'cacheDriver' => config('cache.default'),
            'queueDriver' => config('queue.default'),
            'dbConnection' => config('database.default'),
            'dbStatus' => $this->getDbStatus(),
            'cacheStatus' => $this->getCacheStatus(),
            'queueSize' => $this->getQueueSize(),
            'diskFree' => $this->getDiskFree(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? php_sapi_name(),
        ];
    }

    protected function getDbStatus(): string
    {
        try {
            DB::connection()->getPdo();
            return 'Connected';
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    protected function getCacheStatus(): string
    {
        try {
            $testKey = '_status_page_test';
            Cache::put($testKey, 'ok', 10);
            return Cache::get($testKey) === 'ok' ? 'OK' : 'Error';
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    protected function getQueueSize(): ?int
    {
        try {
            return Queue::size();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getDiskFree(): string
    {
        $bytes = @disk_free_space(base_path());
        if ($bytes === false) {
            return 'Unknown';
        }
        $gb = $bytes / 1024 / 1024 / 1024;
        return number_format($gb, 2) . ' GB';
    }
}
