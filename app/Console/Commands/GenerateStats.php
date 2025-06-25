<?php

namespace App\Console\Commands;

use App\Services\HerbUsageStatistics;
use App\Services\VariantStatisticsService;
use Illuminate\Console\Command;

class GenerateStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:generate {entity?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate statistics for a given entity';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->hasArgument('entity') && str($this->argument('entity'))->isNotEmpty()) {
            switch ($this->argument('entity')) {
                case 'herb' | 'herbs':
                    HerbUsageStatistics::generateAll();
                    break;
                case 'variant' | 'variants':
                    VariantStatisticsService::generateAll();
                    break;
                default:
                    $this->error('Unknown entity: ' . $this->argument('entity'));
            }
        } else {
            $this->info("No entity, generating all statistics");
            $this->newLine();

            $this->info('Herb usage statistics...');
            HerbUsageStatistics::generateAll();

            $this->info('Variant statistics...');
            VariantStatisticsService::generateAll();
        }
    }
}
