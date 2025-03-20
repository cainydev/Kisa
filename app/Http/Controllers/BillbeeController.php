<?php

namespace App\Http\Controllers;

use App\Facades\Billbee;
use App\Models\Variant;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use function response;

class BillbeeController extends Controller
{
    public function post(Request $request): Response
    {
        if ($request->query('Action') && $request->query('Action') === 'SetStock') {
            return $this->setStock($request);
        } else {
            return response('Bad Request', 400);
        }
    }

    /**
     * @throws QuotaExceededException
     */
    private function setStock(Request $request): JsonResponse
    {
        if (!$request->has(['ProductId', 'AvailableStock'])) {
            return response()->json('Bad Request', 400);
        }

        $product_id = $request->get('ProductId');
        $new_stock = $request->get('AvailableStock');

        $products = Variant::where('billbee_id', $product_id)->get();

        if ($products->isEmpty()) {
            $billbee_product = Billbee::products()->getProduct($product_id);
            $sku = $billbee_product->data->sku;
            $mainnumber = str($sku)->beforeLast('.');
            $ordernumber = str($sku)->after($mainnumber);
            $products = Variant::where('ordernumber', $ordernumber)
                ->whereRelation('product', 'mainnumber', $mainnumber)->get();
        }

        if ($products->isEmpty()) {
            return response()->json('Product not found', 400);
        } else if ($products->count() > 1) {
            return response()->json('Multiple products found', 400);
        }

        $product = $products->first();
        $product->stock = $new_stock;
        $product->save();

        return response()->json('Stock updated', 200);
    }

    public function get(Request $request): JsonResponse
    {
        if ($request->query('Action') && $request->query('Action') === 'GetOrders') {
            return $this->getOrders();
        } else {
            return response()->json('Bad Request', 400);
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

}
