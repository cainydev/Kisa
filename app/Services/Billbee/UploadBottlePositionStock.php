<?php

namespace App\Services\Billbee;

use App\Facades\Billbee;
use App\Models\BottlePosition;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use BillbeeDe\BillbeeAPI\Model\Stock;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pushes a completed bottling position's produced quantity into Billbee and
 * records the resulting stock locally. The Billbee write and the two local
 * writes happen inside a transaction so a mid-flight failure cannot leave the
 * position marked uploaded without its stock recorded (or vice versa).
 */
class UploadBottlePositionStock
{
    /**
     * @throws QuotaExceededException
     */
    public function handle(BottlePosition $position): StockUploadResult
    {
        if ($position->uploaded) {
            return new StockUploadResult(StockUploadStatus::AlreadyUploaded, 'Bereits eingelagert.');
        }

        if (! $position->hasAllBags()) {
            return new StockUploadResult(
                StockUploadStatus::MissingBags,
                'Es sind nicht alle verwendeten Rohstoffe zugewiesen.',
            );
        }

        if (empty($position->charge)) {
            $position->charge = $position->getCharge();
            $position->save();
        }

        if (empty($position->charge) || $position->charge === 'CHARGE_NOT_CALCULATABLE') {
            return new StockUploadResult(
                StockUploadStatus::MissingCharge,
                'Die Charge wurde nicht angegeben und konnte nicht berechnet werden.',
            );
        }

        $billbeeProduct = $position->variant->fetchBillbeeProduct();

        if ($billbeeProduct === null) {
            return new StockUploadResult(
                StockUploadStatus::ProductNotFound,
                'Es konnte kein passendes Billbee-Produkt gefunden werden.',
            );
        }

        try {
            return DB::transaction(function () use ($position, $billbeeProduct) {
                $stock = Stock::fromProduct($billbeeProduct);
                $stock->deltaQuantity = $position->count;
                $stock->newQuantity = $stock->oldQuantity + $position->count;
                $stock->reason = "Einlagerung {$position->charge}";

                $response = Billbee::products()->updateStock($stock);
                $newStock = $response->data->currentStock;

                $position->variant->update(['stock' => $newStock]);
                $position->update(['uploaded' => true]);

                return new StockUploadResult(
                    StockUploadStatus::Uploaded,
                    'Neuer Bestand in Billbee: '.$newStock,
                    (int) $newStock,
                );
            });
        } catch (QuotaExceededException $e) {
            throw $e;
        } catch (Throwable $e) {
            return new StockUploadResult(
                StockUploadStatus::Failed,
                'Es ist ein Fehler aufgetreten: '.$e->getMessage(),
            );
        }
    }
}
