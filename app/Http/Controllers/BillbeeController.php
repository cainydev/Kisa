<?php

namespace App\Http\Controllers;

use App\Facades\Billbee;
use App\Models\Order;
use App\Models\Variant;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use function response;

class BillbeeController extends Controller
{
    /**
     * @throws QuotaExceededException
     */
    public function post(Request $request): Response
    {
        if ($request->query('Action')) {
            return match ($request->query('Action')) {
                'SetStock' => $this->setStock($request),
                'SetOrderState' => $this->setOrderState($request)
            };
        } else {
            return response('Bad Request', 400);
        }
    }

    /**
     * @throws QuotaExceededException
     */
    private function setStock(Request $request): Response
    {
        if (!$request->has(['ProductId', 'AvailableStock'])) {
            return response('Bad Request', 400);
        }


        $productId = $request->get('ProductId');
        $newStock = $request->get('AvailableStock');

        Log::info("[BillbeeController.setStock]: Got setStock request for $productId with new stock: $newStock");

        /** @var Collection<Variant> $variants */
        $variants = Variant::where('billbee_id', $productId)->get();

        if ($variants->isEmpty()) {
            $billbeeProduct = Billbee::products()->getProduct($productId);
            $variants = Variant::where('sku', $billbeeProduct->data->sku)->get();
        }

        if ($variants->isEmpty()) {
            return response('Product not found', 400);
        } else if ($variants->count() > 1) {
            return response('Multiple products found', 400);
        }

        $variant = $variants->first();
        $oldStock = $variant->stock;
        $variant->stock = $newStock;

        $productName = $variant->product->name;
        Log::info("[BillbeeController.setStock]: Updated stock of $productName ({$variant->size}g) from $oldStock => $newStock");

        return response('Stock updated');
    }

    public function get(Request $request): JsonResponse|Response
    {
        if ($request->query('Action') && $request->query('Action') === 'GetOrders') {
            return $this->getOrders();
        } else {
            return response('Bad Request', 400);
        }
    }

    private function getOrders(): JsonResponse
    {
        return response()->json([
            'paging' => [
                'page' => 1,
                'totalCount' => 1,
                'totalPages' => 1
            ],
            'orders' => []
        ], 200);
    }

    private function setOrderState(Request $request)
    {
        if (!$request->has('OrderId') && !$request->has('NewStateId')) {
            return response()->json('Bad Request', 400);
        }

        $order = Order::where('billbee_id', $request->get('OrderId'))->firstOrFail();

        $order->status = $request->get('NewStateId');
        $order->save();

        Log::info("[BillbeeController.setOrderState]: Updated order state of order #{$order->order_number} to {$order->status}");

        return response('Order state updated');
    }

}
