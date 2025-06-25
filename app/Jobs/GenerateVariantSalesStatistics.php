<?php

namespace App\Jobs;

use App\Models\Variant;
use App\Services\VariantStatisticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Log;
use function collect;
use function is_array;
use function microtime;

class GenerateVariantSalesStatistics implements ShouldQueue
{
    use Queueable;

    private Collection $variants;

    public function __construct(Collection|Model|array|null $variants = null)
    {
        if ($variants === null) {
            $this->variants = Variant::all();
        } else if ($variants instanceof Model) {
            $this->variants = collect([$variants]);
        } elseif ($variants instanceof Collection) {
            $this->variants = $variants;
        } elseif (is_array($variants)) {
            $this->variants = collect($variants);
        } else {
            $this->variants = collect();
        }
    }

    public function handle(): void
    {
        $time = microtime(true);

        VariantStatisticsService::generate($this->variants);

        Log::info("VariantStatisticsService: Finished all variants in " . (microtime(true) - $time) . " seconds");
    }
}
