<?php

namespace App\Http\Controllers;

use App\Facades\Billbee;
use App\Models\Order;
use App\Models\Variant;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            try {
                $billbeeProduct = Billbee::products()->getProduct($productId);
                $variants = Variant::where('sku', $billbeeProduct->data->sku)->get();
            } catch (Exception) {
                return response('Product not found', 400);
            }
        }

        if ($variants->isEmpty()) {
            return response('Product not found', 400);
        } else if ($variants->count() > 1) {
            return response('Multiple products found', 400);
        }

        $variant = $variants->first();
        $oldStock = $variant->stock;
        $variant->stock = $newStock;
        $variant->save();

        $productName = $variant->product->name;
        Log::info("[BillbeeController.setStock]: Updated stock of $productName ({$variant->size}g) from $oldStock => $newStock");

        return response('Stock updated');
    }

    public function get(Request $request): JsonResponse|Response
    {
        if ($request->query('Action')) {
            return match (Str::trim($request->query('Action'))) {
                'GetOrders' => $this->getOrders(),
                'GetProduct' => $this->getProduct($request),
            };
        } else {
            return response('Bad Request', 400);
        }
    }

    private function getOrders(): JsonResponse
    {
        Log::info("[BillbeeController.getOrders]: Got getOrders request. Returning empty.");

        return response()->json([
            'paging' => [
                'page' => 1,
                'totalCount' => 1,
                'totalPages' => 1
            ],
            'orders' => []
        ]);
    }

    private function getProduct(Request $request): JsonResponse
    {
        if (!$request->has('ProductId') || $request->get('ProductId') === '') {
            Log::info("[BillbeeController.getProduct]: Got getProduct request with bad parameters.", [
                'ProductId' => $request->get('ProductId')
            ]);

            return response()->json('Bad Request', 400);
        }

        $productId = $request->get('ProductId');
        Log::info("[BillbeeController.getProduct]: Got getProduct request for {$productId}.");

        $variant = Variant::with('product')
            ->where('billbee_id', $productId)
            ->orWhere('sku', $productId)
            ->first();

        if (!$variant) {
            Log::warning("[BillbeeController.getProduct]: Could find variant for {$productId}.");
            return response()->json('Bad Request', 400);
        }

        Log::info("[BillbeeController.getProduct]: Returning product details for {$variant->name}.");

        return response()->json([
            'id' => $variant->billbee_id,
            'title' => $variant->name,
            'quantity' => $variant->stock,
            'sku' => $variant->sku,
        ]);
    }

    private function setOrderState(Request $request)
    {
        if (!$request->has('OrderId') && !$request->has('NewStateId')) {
            Log::info("[BillbeeController.setOrderState]: Got setOrderState request with bad parameters.", [
                'OrderId' => $request->get('OrderId'),
                'NewStateId' => $request->get('NewStateId')
            ]);

            return response()->json('Bad Request', 400);
        }

        Log::info("[BillbeeController.setOrderState]: Got setOrderState request.", [
            'OrderId' => $request->get('OrderId'),
            'NewStateId' => $request->get('NewStateId')
        ]);

        $order = Order::where('billbee_id', $request->get('OrderId'))->firstOrFail();

        $order->status = $request->get('NewStateId');
        $order->save();

        Log::info("[BillbeeController.setOrderState]: Updated order state of order #{$order->order_number} to {$order->status}");

        return response('Order state updated');
    }
}
