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
            $this->herb->setRedisAveragePerDay(0);
            $this->herb->setRedisAveragePerMonth(0);
            $this->herb->setRedisAveragePerYear(0);
            $this->herb->setRedisBought(0);
            $this->herb->setRedisUsed(0);
            $this->herb->setRedisRemaining(0);
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
            $current = $bag->getCurrentWithTrashed();
            $remaining += $current;

            $bag->setRedisCurrent($current);
        }

        $daysSinceBought = $firstBought->floatDiffInDays(now());
        $monthsSinceBought = $firstBought->floatDiffInMonths(now());
        $yearsSinceBought = $firstBought->floatDiffInYears(now());
        $used = $bought - $remaining;

        $this->herb->setRedisAveragePerDay($used / $daysSinceBought);
        $this->herb->setRedisAveragePerMonth($used / $monthsSinceBought);
        $this->herb->setRedisAveragePerYear($used / $yearsSinceBought);
        $this->herb->setRedisBought($bought);
        $this->herb->setRedisUsed($used);
        $this->herb->setRedisRemaining($remaining);
    }
}
