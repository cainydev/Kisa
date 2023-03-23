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
    public $trashGate;
    public $startDate;
    public $endDate;

    public function __construct(Herb $herb, $trashGate = 100, $startDate = null, $endDate = null)
    {
        $this->herb = $herb;
        $this->trashGate = $trashGate;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
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
            $this->herb->setRedisGrammRemaining(0);
            $this->herb->setRedisDaysRemaining(0);

            return;
        }

        $firstBought = now();
        $bought = 0;
        $gramm_remaining = 0;

        foreach ($bags as $bag) {
            if ($bag->delivery != null) {
                if ($bag->delivery->delivered_date < $firstBought) {
                    $firstBought = $bag->delivery->delivered_date;
                }
            }

            $current = $bag->getCurrent();
            $percentTrashed = ($bag->trashed / $bag->size) * 100;

            if ($percentTrashed > $this->trashGate) {
                $bought += $bag->size - $bag->trashed;
                $gramm_remaining += $current;
            } else {
                $bought += $bag->size;
                $gramm_remaining += $bag->getCurrentWithTrashed();
            }

            $bag->setRedisCurrent($bag->getCurrentWithTrashed());
        }

        $daysSinceBought = $firstBought->floatDiffInDays(now());
        $monthsSinceBought = $firstBought->floatDiffInMonths(now());
        $yearsSinceBought = $firstBought->floatDiffInYears(now());
        $used = $bought - $gramm_remaining;

        $this->herb->setRedisAveragePerDay($used / $daysSinceBought);
        $this->herb->setRedisAveragePerMonth($used / $monthsSinceBought);
        $this->herb->setRedisAveragePerYear($used / $yearsSinceBought);
        $this->herb->setRedisBought($bought);
        $this->herb->setRedisUsed($used);
        $this->herb->setRedisGrammRemaining($gramm_remaining);
        $this->herb->setRedisDaysRemaining($gramm_remaining / ($used / $daysSinceBought));
    }
}
