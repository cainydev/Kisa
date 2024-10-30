<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BillbeeSettings extends Settings
{
    public bool $enabled;

    public ?string $username;

    public ?string $password;

    public ?string $key;

    public static function group(): string
    {
        return 'billbee';
    }

    public static function encrypted(): array
    {
        return [
            'username',
            'password',
            'key'
        ];
    }
}
