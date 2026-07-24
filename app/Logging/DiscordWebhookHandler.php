<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Spatie\DiscordAlerts\Facades\DiscordAlert;
use function filter_var;
use function is_string;

class DiscordWebhookHandler extends AbstractProcessingHandler
{
    private const array LEVEL_EMOJIS = [
        'DEBUG' => '🐛',
        'INFO' => 'ℹ️',
        'NOTICE' => '📢',
        'WARNING' => '⚠️',
        'ERROR' => '❌',
        'CRITICAL' => '🔥',
        'ALERT' => '🚨',
        'EMERGENCY' => '💀',
    ];

    public function __construct(
        Level $level = Level::Debug,
        bool  $bubble = true,
    )
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if ($record->level->isLowerThan($this->level)) {
            return;
        }

        $webhookUrl = config('discord-alerts.webhook_urls.default');

        if (! is_string($webhookUrl) || filter_var($webhookUrl, FILTER_VALIDATE_URL) === false) {
            return;
        }

        $emoji = self::LEVEL_EMOJIS[$record->level->getName()] ?? '📝';

        $message = sprintf(
            '%s **%s** %s',
            $emoji,
            $record->level->getName(),
            $record->message,
            json_encode($record->context)
        );

        DiscordAlert::message($message);
    }
}
