<?php

namespace App\Services\Billbee;

/**
 * Machine-readable outcome of pushing a bottling position's produced quantity
 * to Billbee. Lets the calling UI layer pick a notification without the
 * service knowing anything about Filament.
 */
enum StockUploadStatus: string
{
    case AlreadyUploaded = 'already_uploaded';
    case Uploaded = 'uploaded';
    case MissingBags = 'missing_bags';
    case MissingCharge = 'missing_charge';
    case ProductNotFound = 'product_not_found';
    case Failed = 'failed';

    public function isSuccess(): bool
    {
        return $this === self::Uploaded || $this === self::AlreadyUploaded;
    }
}
