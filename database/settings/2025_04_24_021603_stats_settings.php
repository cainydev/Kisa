<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('stats.startDate', now()->subYears(3));
        $this->migrator->add('stats.autoEnabled', true);
        $this->migrator->add('stats.autoTime', '02:00');
    }

    public function down(): void
    {
        $this->migrator->delete('stats.startDate');
        $this->migrator->delete('stats.autoEnabled');
        $this->migrator->delete('stats.autoTime');
    }
};
