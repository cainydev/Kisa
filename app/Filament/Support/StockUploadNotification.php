<?php

namespace App\Filament\Support;

use App\Services\Billbee\StockUploadResult;
use App\Services\Billbee\StockUploadStatus;
use Filament\Notifications\Notification;

/**
 * Bridges a {@see StockUploadResult} (a UI-agnostic service result) into a
 * Filament notification, keeping the notification wording in the UI layer.
 */
class StockUploadNotification
{
    public static function make(StockUploadResult $result): Notification
    {
        $notification = Notification::make();

        return match ($result->status) {
            StockUploadStatus::Uploaded, StockUploadStatus::AlreadyUploaded => $notification
                ->title('Einlagern erfolgreich')
                ->body($result->message)
                ->success(),
            StockUploadStatus::MissingBags => $notification
                ->title('Einlagern fehlgeschlagen')
                ->body($result->message)
                ->warning(),
            default => $notification
                ->title('Einlagern fehlgeschlagen')
                ->body($result->message)
                ->danger(),
        };
    }
}
