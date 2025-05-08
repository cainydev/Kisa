<?php

namespace App\Settings;

use Carbon\Carbon;
use Spatie\LaravelSettings\Settings;

class StatsSettings extends Settings
{

    public Carbon $startDate;

    public bool $autoEnabled;
    public string $autoTime;

    public static function group(): string
    {
        return 'stats';
    }
}
