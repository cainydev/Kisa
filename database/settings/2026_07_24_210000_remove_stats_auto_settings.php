<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * The stats settings group is gone: autoEnabled/autoTime were never read
     * (the schedule lives in routes/console.php), and the window start is now
     * derived from the earliest recorded event instead of a stored date.
     */
    public function up(): void
    {
        $this->migrator->delete('stats.startDate');
        $this->migrator->delete('stats.autoEnabled');
        $this->migrator->delete('stats.autoTime');
    }

    public function down(): void
    {
        $this->migrator->add('stats.startDate', now()->subYears(3));
        $this->migrator->add('stats.autoEnabled', true);
        $this->migrator->add('stats.autoTime', '02:00');
    }
};
