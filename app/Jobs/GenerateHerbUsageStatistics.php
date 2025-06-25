<?php

namespace App\Jobs;

use App\Models\Herb;
use App\Services\HerbUsageStatistics;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Log;

class GenerateHerbUsageStatistics implements ShouldQueue
{
    use Queueable;

    private Collection $herbs;

    public function __construct(Collection|Model|array|null $herbs = null)
    {
        if ($herbs === null) {
            $this->herbs = Herb::all();
        } else if ($herbs instanceof Model) {
            $this->herbs = collect([$herbs]);
        } elseif ($herbs instanceof Collection) {
            $this->herbs = $herbs;
        } elseif (is_array($herbs)) {
            $this->herbs = collect($herbs);
        } else {
            $this->herbs = collect();
        }
    }

    public function handle(): void
    {
        $time = microtime(true);

        HerbUsageStatistics::generate($this->herbs);

        Log::info("GenerateHerbUsageStatistics: Finished all herbs in " . (microtime(true) - $time) . " seconds");
    }
}
