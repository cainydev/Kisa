<?php

namespace App\Console\Commands;

use App\Facades\Billbee;
use App\Models\Order;
use App\Models\Variant;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use BillbeeDe\BillbeeAPI\Model\Order as BillbeeOrder;
use BillbeeDe\BillbeeAPI\Model\OrderItem as BillbeeOrderItem;
use BillbeeDe\BillbeeAPI\Model\Payment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchBillbeeOrders extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billbee:orders
                            {--perpage=100 : Page size when fetching}
                            {--after= : Only orders after this date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches orders from Billbee and updates the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $page = 1;
        $pageSize = (int)$this->option('perpage');

        $minOrderDate = $this->option('after')
            ? Carbon::parse($this->option('after'))
            : null;

        $this->info($minOrderDate
            ? "Fetching orders since {$minOrderDate->toDateTimeString()}..."
            : "Fetching all orders...");

        try {
            $firstResponse = Billbee::orders()->getOrders($page, $pageSize, $minOrderDate);
            $totalRows = $firstResponse->paging->totalRows ?? count($firstResponse->data);

            $bar = $this->output->createProgressBar($totalRows);
            $bar->start();

            do {
                try {
                    $response = ($page === 1 && isset($firstResponse))
                        ? $firstResponse
                        : Billbee::orders()->getOrders($page, $pageSize, $minOrderDate);

                    $this->processBatch($response->data);

                    $bar->advance(count($response->data));

                    $totalPages = $response->paging->totalPages ?? 1;
                    $page = ($page < $totalPages) ? $page + 1 : false;

                    unset($response);

                } catch (QuotaExceededException $e) {
                    $this->newLine();
                    $this->warn("API Quota exceeded. Pausing for 2 seconds...");
                    sleep(2);
                    continue;
                } catch (Throwable $e) {
                    $this->newLine();
                    $this->error("Error on page $page: " . $e->getMessage());
                    Log::error($e);
                    return self::FAILURE;
                }

            } while ($page !== false);

            $bar->finish();
            $this->newLine(2);
            $this->info("Sync complete.");

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Critical error: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Process a batch of orders within a transaction.
     * @throws Throwable
     */
    private function processBatch(array $billbeeOrders): void
    {
        foreach ($billbeeOrders as $order) {
            if (!$order instanceof BillbeeOrder) continue;

            DB::transaction(function () use ($order) {
                $this->syncOrder($order);
            });
        }
    }

    private function syncOrder(BillbeeOrder $order): void
    {
        $paymentMethod = $order->paymentMethod;

        if (!empty($order->payments)) {
            $payment = $order->payments[0];
            if ($payment instanceof Payment) {
                $paymentMethod = $payment->sourceTechnology;
            }
        }

        $taxRates = [0, $order->taxRate1, $order->taxRate2];

        $orderModel = Order::updateOrCreate(
            ['billbee_id' => $order->id],
            [
                'status' => $order->state,
                'order_number' => $order->orderNumber,
                'date' => $order->createdAt,
                'shipped_at' => $order->shippedAt,
                'paid_at' => $order->payedAt,
                'payment_method' => $paymentMethod,
                'platform' => $order->seller?->platform,
                'total' => $order->totalCost,
                'currency' => $order->currency,
            ]
        );

        foreach ($order->orderItems as $item) {
            if (!$item instanceof BillbeeOrderItem) continue;
            if (empty($item->billbeeId) || $item->quantity <= 0) continue;

            $variant = Variant::where('sku', $item->product->sku)->first();

            if (!$variant) continue;

            $orderModel->positions()->updateOrCreate(
                ['billbee_id' => $item->billbeeId],
                [
                    'order_id' => $orderModel->id,
                    'variant_id' => $variant->id,
                    'quantity' => $item->quantity,
                    'price' => $item->quantity > 0 ? ($item->totalPrice / $item->quantity) : 0,
                    'tax_percent' => $taxRates[$item->taxIndex] ?? 0,
                ]
            );
        }
    }
}
