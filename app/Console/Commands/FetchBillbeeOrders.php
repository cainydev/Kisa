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
use Illuminate\Support\Carbon;

class FetchBillbeeOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billbee:orders {--perpage=250 : Page size when fetching} {--after=null : Only orders after this date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches all orders at once and updates the database';

    /**
     * Execute the console command.
     * @throws QuotaExceededException
     */
    public function handle(): void
    {
        $orders = collect();
        $page = 1;
        $pageSize = intval($this->option('perpage'));

        $minOrderDate = $this->option('after') ? Carbon::parse($this->option('after')) : null;
        if ($minOrderDate) {
            $this->info('Fetching orders from Billbee since ' . $minOrderDate->toDateTimeString());
        } else {
            $this->info('Fetching all orders from Billbee...');
        }
        $this->newLine();

        $pagingInfo = Billbee::orders()->getOrders($page, $pageSize, $minOrderDate)->paging;

        if ($pagingInfo === null) {
            try {
                $response = Billbee::orders()->getOrders($page, $pageSize, $minOrderDate);
                $orders->push(...$response->data);
            } catch (QuotaExceededException $e) {
                $this->warn('Billbee API quota exceeded. Let\'s wait a second.');
                sleep(1);
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $bar = $this->output->createProgressBar($pagingInfo['TotalRows']);
            $bar->start();

            while ($page) {
                try {
                    $response = Billbee::orders()->getOrders($page, $pageSize, $minOrderDate);
                    $orders->push(...$response->data);
                    $page = $response->paging['TotalPages'] == $page ? false : $page + 1;
                    $bar->advance(count($response->data));
                } catch (QuotaExceededException $e) {
                    $this->warn('Billbee API quota exceeded. Let\'s wait a second.');
                    sleep(1);
                    continue;
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                    continue;
                }
            }

            $bar->finish();
            $this->newLine(2);
        }

        $this->info('Updating orders...');
        $this->newLine();
        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            if (!$order instanceof BillbeeOrder) continue;

            $paymentMethod = $order->paymentMethod;
            if (!empty($order->payments)) {
                $payment = $order->payments[0];
                if ($payment instanceof Payment)
                    $paymentMethod = $payment->sourceTechnology;
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
                if (!$orderModel instanceof Order) continue;
                if (!$item instanceof BillbeeOrderItem) continue;
                if (empty($item->billbeeId)) continue;
                if ($item->quantity <= 0) continue;

                $variant = Variant::where('sku', $item->product->sku)->first();
                if ($variant === null) continue;

                $orderModel->positions()->updateOrCreate(
                    ['billbee_id' => $item->billbeeId],
                    [
                        'order_id' => $orderModel->id,
                        'variant_id' => $variant->id,
                        'quantity' => $item->quantity,
                        'price' => $item->totalPrice / $item->quantity,
                        'tax_percent' => $taxRates[$item->taxIndex],
                    ]
                );
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Done.');
    }
}
