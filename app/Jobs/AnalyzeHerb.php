<?php

namespace App\Jobs;

use App\Models\Herb;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class AnalyzeHerb implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, Batchable, SerializesModels;

    public $herb;

    public function __construct(Herb $herb)
    {
        $this->herb = $herb;
    }

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $bags = $this->herb->bags;

        if ($bags->count() == 0) {
            Redis::set('herb:' . $this->herb->id . ':per.day', 0);
            Redis::set('herb:' . $this->herb->id . ':per.month', 0);
            Redis::set('herb:' . $this->herb->id . ':per.year', 0);
            Redis::set('herb:' . $this->herb->id . ':bought', 0);
            Redis::set('herb:' . $this->herb->id . ':used', 0);
            Redis::set('herb:' . $this->herb->id . ':remaining', 0);
            return;
        }

        $firstBought = now();
        $bought = 0;
        $remaining = 0;

        foreach ($bags as $bag) {
            if ($bag->delivery != null)
                if ($bag->delivery->delivered_date < $firstBought)
                    $firstBought = $bag->delivery->delivered_date;

            $bought += $bag->size;
            $current = $bag->getCurrent();
            $remaining += $current;

            Redis::set('bag:' . $bag->id . ':remaining', $current);
        }

        $daysSinceBought = $firstBought->floatDiffInDays(now());
        $monthsSinceBought = $firstBought->floatDiffInMonths(now());
        $yearsSinceBought = $firstBought->floatDiffInYears(now());
        $used = $bought - $remaining;

        Redis::set('herb:' . $this->herb->id . ':per.day', $used / $daysSinceBought);
        Redis::set('herb:' . $this->herb->id . ':per.month', $used / $monthsSinceBought);
        Redis::set('herb:' . $this->herb->id . ':per.year', $used / $yearsSinceBought);
        Redis::set('herb:' . $this->herb->id . ':bought', $bought);
        Redis::set('herb:' . $this->herb->id . ':used', $used);
        Redis::set('herb:' . $this->herb->id . ':remaining', $remaining);
    }
}
