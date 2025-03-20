<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->addEncrypted('billbee.enabled', false);
        $this->migrator->addEncrypted('billbee.username', null);
        $this->migrator->addEncrypted('billbee.password', null);
        $this->migrator->addEncrypted('billbee.key', null);
        $this->migrator->addEncrypted('billbee.customShopKey', null);
    }

    public function down(): void
    {
        $this->migrator->delete('billbee.enabled');
        $this->migrator->delete('billbee.username');
        $this->migrator->delete('billbee.password');
        $this->migrator->delete('billbee.key');
        $this->migrator->delete('billbee.customShopKey');
    }
};
