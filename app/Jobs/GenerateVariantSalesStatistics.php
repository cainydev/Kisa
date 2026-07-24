<?php

namespace App\Jobs;

use App\Models\Variant;
use App\Services\VariantStatisticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GenerateVariantSalesStatistics implements ShouldQueue
{
    use Queueable;

    /**
     * Variant ids to regenerate, or null for all. Only ids are stored so the
     * queue payload stays small.
     *
     * @var array<int, int>|null
     */
    private ?array $variantIds;

    public function __construct(Collection|Model|array|null $variants = null)
    {
        $this->variantIds = match (true) {
            $variants === null => null,
            $variants instanceof Model => [$variants->getKey()],
            $variants instanceof Collection => $variants->map(fn ($variant) => $variant instanceof Model ? $variant->getKey() : $variant)->all(),
            default => array_map(fn ($variant) => $variant instanceof Model ? $variant->getKey() : $variant, $variants),
        };
    }

    public function handle(): void
    {
        $time = microtime(true);

        Variant::query()
            ->when($this->variantIds !== null, fn ($query) => $query->whereIn('id', $this->variantIds))
            ->chunk(100, fn ($variants) => VariantStatisticsService::generate($variants));

        Log::info('VariantStatisticsService: Finished in '.(microtime(true) - $time).' seconds');
    }
}
