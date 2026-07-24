<?php

namespace App\Services\Billbee;

/**
 * The result of a stock upload attempt: a status and a human-readable message
 * describing what happened, plus the new Billbee stock level on success.
 */
readonly class StockUploadResult
{
    public function __construct(
        public StockUploadStatus $status,
        public string $message,
        public ?int $newStock = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }
}
