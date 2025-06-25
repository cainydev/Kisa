<?php

namespace App\Settings;

use Carbon\Carbon;
use Spatie\LaravelSettings\Settings;

class StatsSettings extends Settings
{
    public Carbon $startDate;

    public bool $autoEnabled = true;
    public string $autoTime = '02:00:00';

    public static function group(): string
    {
        return 'stats';
    }
}
