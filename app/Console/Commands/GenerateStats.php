<?php

namespace App\Console\Commands;

use App\Services\HerbStatisticsService;
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
        $entity = $this->argument('entity');

        if ($entity && str($entity)->isNotEmpty()) {
            switch ($entity) {
                case 'herb':
                case 'herbs':
                    $this->info('Generating Herb statistics...');
                    HerbStatisticsService::generateAll();
                    $this->info('Done.');
                    break;

                case 'variant':
                case 'variants':
                    $this->info('Generating Variant statistics...');
                    VariantStatisticsService::generateAll();
                    $this->info('Done.');
                    break;

                default:
                    $this->error('Unknown entity: ' . $entity);
            }
        } else {
            $this->info("No entity specified, generating all statistics...");
            $this->newLine();

            $this->components->task('Generating Herb Statistics', function () {
                HerbStatisticsService::generateAll();
            });

            $this->components->task('Generating Variant Statistics', function () {
                VariantStatisticsService::generateAll();
            });

            $this->newLine();
            $this->info('All statistics generated successfully.');
        }
    }
}
