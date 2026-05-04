<?php

namespace App\Console\Commands;

use App\Facades\Billbee;
use App\Models\Order;
use App\Models\Variant;
use App\Services\VariantStatisticsService;
use BillbeeDe\BillbeeAPI\Exception\QuotaExceededException;
use BillbeeDe\BillbeeAPI\Model\Order as BillbeeOrder;
use BillbeeDe\BillbeeAPI\Model\OrderItem as BillbeeOrderItem;
use BillbeeDe\BillbeeAPI\Model\Payment;
use BillbeeDe\BillbeeAPI\Type\ProductLookupBy;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;
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

    /** @var array<string, Variant> SKU => Variant lookup cache populated lazily. */
    private array $variantsBySku = [];

    /** @var array<int, true> Set of variant IDs touched across all batches. */
    private array $touchedVariantIds = [];

    private int $ordersProcessed = 0;

    private int $ordersCreated = 0;

    private int $ordersUpdated = 0;

    private int $positionsSynced = 0;

    private int $positionsSkippedNoVariant = 0;

    public function handle(): int
    {
        $startedAt = microtime(true);
        $page = 1;
        $pageSize = (int) $this->option('perpage');

        $minOrderDate = $this->option('after')
            ? Carbon::parse($this->option('after'))
            : null;

        $this->components->info($minOrderDate
            ? "Fetching orders since {$minOrderDate->toDateTimeString()}"
            : 'Fetching all orders');

        $this->preloadVariants();

        try {
            $firstResponse = Billbee::orders()->getOrders($page, $pageSize, $minOrderDate);
            $totalRows = $firstResponse->paging->totalRows ?? count($firstResponse->data);
            $totalPages = $firstResponse->paging->totalPages ?? 1;

            $this->components->twoColumnDetail('Total orders', (string) $totalRows);
            $this->components->twoColumnDetail('Total pages', (string) $totalPages);
            $this->components->twoColumnDetail('Page size', (string) $pageSize);
            $this->newLine();

            $bar = $this->output->createProgressBar($totalRows);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%/%estimated:-6s%  %message%');
            $bar->setMessage('starting…');
            $bar->start();

            do {
                try {
                    $bar->setMessage("fetching page {$page}/{$totalPages}");
                    $bar->display();

                    $response = ($page === 1 && isset($firstResponse))
                        ? $firstResponse
                        : Billbee::orders()->getOrders($page, $pageSize, $minOrderDate);

                    $batchStart = microtime(true);
                    $this->processBatch($response->data, $bar, $page, $totalPages);
                    $batchMs = (int) ((microtime(true) - $batchStart) * 1000);

                    if ($this->getOutput()->isVerbose()) {
                        $bar->clear();
                        $this->components->twoColumnDetail(
                            "page {$page}/{$totalPages} — ".count($response->data).' orders',
                            "{$batchMs}ms"
                        );
                        $bar->display();
                    }

                    $totalPages = $response->paging->totalPages ?? 1;
                    $page = ($page < $totalPages) ? $page + 1 : false;

                    unset($response);

                } catch (QuotaExceededException $e) {
                    $bar->setMessage('quota exceeded — pausing 2s');
                    $bar->display();
                    sleep(2);

                    continue;
                } catch (Throwable $e) {
                    $bar->clear();
                    $this->components->error("Error on page {$page}: ".$e->getMessage());
                    Log::error($e);

                    return self::FAILURE;
                }

            } while ($page !== false);

            $bar->setMessage('done fetching');
            $bar->finish();
            $this->newLine(2);

            $this->refreshTouchedVariants();

            $this->newLine();
            $this->components->info(sprintf(
                'Sync complete in %.1fs',
                microtime(true) - $startedAt
            ));
            $this->components->twoColumnDetail('Orders processed', (string) $this->ordersProcessed);
            $this->components->twoColumnDetail('  – created', (string) $this->ordersCreated);
            $this->components->twoColumnDetail('  – updated', (string) $this->ordersUpdated);
            $this->components->twoColumnDetail('Positions synced', (string) $this->positionsSynced);
            if ($this->positionsSkippedNoVariant > 0) {
                $this->components->twoColumnDetail('Positions skipped (unknown SKU)', (string) $this->positionsSkippedNoVariant);
            }
            $this->components->twoColumnDetail('Variants touched', (string) count($this->touchedVariantIds));

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->components->error('Critical error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Preload every known variant once so per-position SKU lookups are O(1).
     */
    private function preloadVariants(): void
    {
        $this->variantsBySku = Variant::query()
            ->whereNotNull('sku')
            ->get()
            ->keyBy('sku')
            ->all();
    }

    /**
     * Process a batch of orders within a transaction.
     *
     * @throws Throwable
     */
    private function processBatch(array $billbeeOrders, ProgressBar $bar, int $page, int $totalPages): void
    {
        foreach ($billbeeOrders as $order) {
            if (! $order instanceof BillbeeOrder) {
                continue;
            }

            $orderLabel = $order->orderNumber ?: ('#'.$order->id);
            $bar->setMessage("page {$page}/{$totalPages} — {$orderLabel}");

            DB::transaction(function () use ($order) {
                $this->syncOrder($order);
            });

            $this->ordersProcessed++;
            $bar->advance();
        }
    }

    /**
     * Refresh stock from Billbee and regenerate stats for every touched variant
     * across the whole run. Deduplicated, so each variant pays the API + stats
     * cost exactly once regardless of how many orders mentioned it.
     */
    private function refreshTouchedVariants(): void
    {
        if (empty($this->touchedVariantIds)) {
            return;
        }

        $variantIds = array_keys($this->touchedVariantIds);
        $variants = Variant::whereIn('id', $variantIds)->get();

        $this->components->info('Refreshing stock for '.$variants->count().' variants');

        $bar = $this->output->createProgressBar($variants->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%/%estimated:-6s%  %message%');
        $bar->start();

        $stockFailures = 0;

        foreach ($variants as $variant) {
            if (empty($variant->sku)) {
                $bar->advance();

                continue;
            }

            $bar->setMessage("stock {$variant->sku}");
            $bar->display();

            try {
                $response = Billbee::products()->getProduct($variant->sku, ProductLookupBy::SKU);
                $product = $response->data ?? null;
                if ($product) {
                    $variant->update([
                        'billbee_id' => $product->id,
                        'ean' => $product->ean,
                        'stock' => $product->stockCurrent ?? 0,
                    ]);
                }
            } catch (QuotaExceededException $e) {
                $bar->setMessage('quota exceeded — pausing 2s');
                $bar->display();
                sleep(2);
            } catch (Throwable $e) {
                $stockFailures++;
                Log::warning("Failed to refresh stock for variant {$variant->id} (sku {$variant->sku}): ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->setMessage('done');
        $bar->finish();
        $this->newLine(2);

        if ($stockFailures > 0) {
            $this->components->warn("{$stockFailures} stock refresh(es) failed (see log).");
        }

        $fresh = $variants->fresh();
        if ($fresh && $fresh->isNotEmpty()) {
            $this->components->info('Regenerating sales statistics');
            $statsBar = $this->output->createProgressBar($fresh->count());
            $statsBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%/%estimated:-6s%');
            $statsBar->start();

            $fresh->chunk(50)->each(function ($chunk) use ($statsBar) {
                VariantStatisticsService::generate($chunk);
                $statsBar->advance($chunk->count());
            });

            $statsBar->finish();
            $this->newLine(2);
        }
    }

    private function syncOrder(BillbeeOrder $order): void
    {
        $paymentMethod = $order->paymentMethod;

        if (! empty($order->payments)) {
            $payment = $order->payments[0];
            if ($payment instanceof Payment) {
                $paymentMethod = $payment->sourceTechnology;
            }
        }

        $taxRates = [0, $order->taxRate1, $order->taxRate2];

        $existed = Order::where('billbee_id', $order->id)->exists();

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

        if ($existed) {
            $this->ordersUpdated++;
        } else {
            $this->ordersCreated++;
        }

        foreach ($order->orderItems as $item) {
            if (! $item instanceof BillbeeOrderItem) {
                continue;
            }
            if (empty($item->billbeeId) || $item->quantity <= 0) {
                continue;
            }

            $sku = $item->product->sku ?? null;
            if (! $sku) {
                continue;
            }

            $variant = $this->variantsBySku[$sku]
                ?? ($this->variantsBySku[$sku] = Variant::where('sku', $sku)->first());

            if (! $variant) {
                $this->positionsSkippedNoVariant++;

                continue;
            }

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

            $this->touchedVariantIds[$variant->id] = true;
            $this->positionsSynced++;
        }
    }
}
