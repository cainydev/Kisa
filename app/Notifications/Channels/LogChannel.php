<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\Notifications\Channels\Discord\DiscordMessage;

/**
 * Sends a notification to the application log instead of an external service.
 *
 * Laravel has no built-in `log` notification channel, so package notifications
 * (spatie/laravel-backup in particular) reach Discord or mail while leaving no
 * trace in laravel.log. Routing them here instead puts them on the default
 * logging stack, which already fans out to both the daily file and Discord —
 * one webhook config, one code path, one record.
 *
 * The log level is derived from the message's own severity: Spatie tags its
 * Discord messages with a colour via ->error()/->warning()/->success(), which
 * is the only severity signal the notification exposes.
 */
class LogChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toDiscord')) {
            return;
        }

        $embed = $notification->toDiscord()->toArray()['embeds'][0] ?? [];

        $title = $embed['title'] ?? class_basename($notification);
        $fields = collect($embed['fields'] ?? [])
            ->mapWithKeys(fn (array $field) => [$field['name'] => $field['value']])
            ->all();

        Log::log($this->levelFor($embed['color'] ?? 0), $title, $fields);
    }

    /**
     * Map the embed colour back to a log level. `toArray()` has already run the
     * hex through `hexdec`, so compare against decimal values.
     */
    private function levelFor(int $color): string
    {
        return match ($color) {
            hexdec(DiscordMessage::COLOR_ERROR) => 'error',
            hexdec(DiscordMessage::COLOR_WARNING) => 'warning',
            default => 'info',
        };
    }
}
