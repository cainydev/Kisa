<?php

namespace App\Console\Commands;

use App\Models\Herb;
use App\Services\HerbStatisticsService;
use App\Support\Stats\HerbStats;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugStats extends Command
{
    protected $signature = 'stats:debug {herb_id}';

    protected $description = 'Deep dive debug into stats generation for a specific herb';

    public function handle(): void
    {
        $id = $this->argument('herb_id');
        $herb = Herb::find($id);

        if (! $herb) {
            $this->error("Herb #{$id} not found.");

            return;
        }

        $this->info("🔍 DEBUGGING HERB: {$herb->name} (ID: {$id})");
        $this->line(str_repeat('-', 50));

        // 1. CHECK WINDOW
        $startDate = HerbStatisticsService::windowStart();
        $daysDiff = (int) $startDate->diffInDays(now());
        $this->comment('📅 Derived Window Start: '.$startDate->toDateString()." ({$daysDiff} days ago)");

        // 2. CHECK SOURCE DATA (SQL)
        $this->line("\n📊 SQL SOURCE CHECK (Last 10 entries):");

        $usageCount = DB::table('ingredients')
            ->join('bags', 'ingredients.bag_id', '=', 'bags.id')
            ->where('bags.herb_id', $herb->id)
            ->where('ingredients.created_at', '>=', $startDate)
            ->count();

        $restockCount = $herb->bags()
            ->where('created_at', '>=', $startDate)
            ->count();

        $this->info("   - Total Usage Records found: {$usageCount}");
        $this->info("   - Total Bags (Restocks) found: {$restockCount}");

        if ($usageCount == 0 && $restockCount == 0) {
            $this->error('   ❌ SOURCE DATA EMPTY! The generator has nothing to work with.');

            return;
        }

        // 3. CHECK STORED STATS
        $this->line("\n💾 STORED STATS CHECK:");
        $stats = HerbStats::for($herb);
        $stockHistory = $stats->stock()->get(); // Dense daily series from the stats column

        if ($stockHistory->isEmpty()) {
            $this->error('   ❌ Stored stats have no stock history.');
            $this->comment('   - Column checked: herbs.stats (regenerate with stats:generate herbs)');

            return;
        }

        $count = $stockHistory->count();
        $firstDate = $stockHistory->keys()->first();
        $lastDate = $stockHistory->keys()->last();

        $this->info("   - Data Points in Redis: {$count}");
        $this->info("   - First Date: {$firstDate}");
        $this->info("   - Last Date:  {$lastDate}");

        // 4. ANALYZE VALUES (Detect Flatline)
        $this->line("\n📉 VALUE ANALYSIS:");

        $uniqueValues = $stockHistory->unique()->values();
        if ($uniqueValues->count() <= 1) {
            $this->warn("   ⚠️ FLATLINE DETECTED: All {$count} days have the exact same value: ".$uniqueValues->first());
        } else {
            $this->info("   ✅ Data varies. Found {$uniqueValues->count()} unique stock levels.");
        }

        // 5. DUMP SAMPLE DATA
        $this->line("\n📝 SAMPLE DATA (First 5 vs Last 5):");

        $this->table(
            ['Date', 'Stock Value'],
            $stockHistory->take(5)->map(fn ($v, $k) => [$k, $v])
                ->merge([['...', '...']])
                ->merge($stockHistory->take(-5)->map(fn ($v, $k) => [$k, $v]))
                ->toArray()
        );

        // 6. CHECK USAGE VS RESTOCK FOR A SPECIFIC DAY
        // Find a day where stock changed
        $lastVal = null;
        $changeDate = null;
        foreach ($stockHistory as $date => $val) {
            if ($lastVal !== null && abs($val - $lastVal) > 0.1) {
                $changeDate = $date;
                break;
            }
            $lastVal = $val;
        }

        if ($changeDate) {
            $this->line("\n🕵️ DEEP DIVE INTO CHANGE ON: {$changeDate}");

            $u = $stats->usage()->get()[$changeDate] ?? 0;
            // We have to query DB for restock manually here as we don't expose it via Reader yet
            $r = DB::table('bags', 'b')
                ->leftJoin('deliveries as d', 'b.delivery_id', '=', 'd.id')
                ->where('b.herb_id', $herb->id)
                ->whereRaw('COALESCE(d.delivered_date, DATE(b.created_at)) = ?', [$changeDate])
                ->sum('b.size');

            $this->info("   - Stock on {$changeDate}: {$stockHistory[$changeDate]}");
            $this->info("   - Usage that day: {$u}");
            $this->info("   - Restock that day: {$r}");

            $calcCheck = ($stockHistory[$changeDate] == ($lastVal - $u + $r)) ? '✅ Matches' : '❌ Mismatch';
            $this->comment("   - Math Check: Previous($lastVal) - Usage($u) + Restock($r) = ".($lastVal - $u + $r)." -> $calcCheck");
        }
    }
}
