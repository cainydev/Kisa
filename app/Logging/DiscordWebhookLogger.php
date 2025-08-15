<?php

namespace App\Logging;

use Monolog\Level;
use Monolog\Logger;
use function is_string;

class DiscordWebhookLogger
{
    /**
     * Create a custom Monolog instance.
     */
    public function __invoke(array $config): Logger
    {
        $logger = new Logger($config['name'] ?? 'discord');

        $level = is_string($config['level']) ? Level::fromName($config['level']) : $config['level'];

        $logger->pushHandler(new DiscordWebhookHandler(
            $level ?? Level::Error,
            $config['bubble'] ?? true,
        ));

        return $logger;
    }
}
