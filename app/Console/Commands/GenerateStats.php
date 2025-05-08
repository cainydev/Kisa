<?php

namespace App\Console\Commands;

use App\Services\HerbUsageStatistics;
use Illuminate\Console\Command;

class GenerateStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:generate {entity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate statistics for a given entity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        switch ($this->argument('entity')) {
            case 'herb' | 'herbs':
                HerbUsageStatistics::generateAll();
        }
    }
}
