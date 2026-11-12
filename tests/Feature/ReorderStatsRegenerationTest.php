<?php

use App\Filament\Widgets\Reorder;
use App\Jobs\RegenerateHerbStatistics;
use App\Livewire\HerbStatsProgress;
use App\Models\Herb;
use App\Models\User;
use App\Services\HerbStatisticsService;
use App\Support\Stats\HerbStatsRegeneration;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'Tester',
        'email' => 'tester@example.test',
        'password' => bcrypt('secret'),
    ]));
});

describe('regeneration tracker', function () {
    it('is idle by default', function () {
        expect(HerbStatsRegeneration::isRunning())->toBeFalse()
            ->and(HerbStatsRegeneration::percentage())->toBeNull();
    });

    it('reports no percentage while waiting for a worker', function () {
        HerbStatsRegeneration::queue();

        expect(HerbStatsRegeneration::isRunning())->toBeTrue()
            ->and(HerbStatsRegeneration::percentage())->toBeNull();
    });

    it('tracks progress and clamps at the total', function () {
        HerbStatsRegeneration::start(4);
        HerbStatsRegeneration::advance();

        expect(HerbStatsRegeneration::percentage())->toBe(25);

        HerbStatsRegeneration::advance(10);

        expect(HerbStatsRegeneration::current())->toBe(['processed' => 4, 'total' => 4]);
    });

    it('ignores advances when no run is active', function () {
        HerbStatsRegeneration::advance();

        expect(HerbStatsRegeneration::isRunning())->toBeFalse();
    });
});

describe('herb stats generation', function () {
    it('reports every herb and clears the run when done', function () {
        Herb::factory()->count(3)->create();
        $seen = [];

        HerbStatisticsService::generate(Herb::all(), function (Herb $herb) use (&$seen) {
            $seen[] = $herb->id;
        });

        expect($seen)->toHaveCount(3);

        HerbStatisticsService::generateAll();

        expect(HerbStatsRegeneration::isRunning())->toBeFalse()
            ->and(Herb::query()->whereNull('stats')->count())->toBe(0);
    });

    it('clears the run when the job fails', function () {
        HerbStatsRegeneration::queue();

        (new RegenerateHerbStatistics)->failed(new RuntimeException('worker died'));

        expect(HerbStatsRegeneration::isRunning())->toBeFalse();
    });
});

describe('reorder widget', function () {
    it('queues a regeneration from the header button', function () {
        Queue::fake();

        Livewire::test(Reorder::class)
            ->assertActionVisible(TestAction::make('regenerateStats')->table())
            ->callAction(TestAction::make('regenerateStats')->table());

        Queue::assertPushed(RegenerateHerbStatistics::class);
        expect(HerbStatsRegeneration::isRunning())->toBeTrue();
    });

    it('swaps the button for the progress bar in the same request as the click', function () {
        Queue::fake();

        Livewire::test(Reorder::class)
            ->assertDontSeeLivewire(HerbStatsProgress::class)
            ->callAction(TestAction::make('regenerateStats')->table())
            ->assertSeeLivewire(HerbStatsProgress::class)
            ->assertDontSee('Neu berechnen');
    });

    it('does not queue a second run while one is in progress', function () {
        Queue::fake();
        HerbStatsRegeneration::start(10);

        Livewire::test(Reorder::class)
            ->callAction(TestAction::make('regenerateStats')->table());

        Queue::assertNothingPushed();
    });

    it('shows the progress bar instead of the button while running', function () {
        HerbStatsRegeneration::start(8);
        HerbStatsRegeneration::advance(2);

        Livewire::test(Reorder::class)
            ->assertSeeLivewire(HerbStatsProgress::class)
            ->assertDontSee('Neu berechnen');
    });

    it('shows the button when idle', function () {
        Livewire::test(Reorder::class)
            ->assertDontSeeLivewire(HerbStatsProgress::class)
            ->assertSee('Neu berechnen');
    });
});

describe('progress bar', function () {
    it('renders the current progress', function () {
        HerbStatsRegeneration::start(8);
        HerbStatsRegeneration::advance(2);

        Livewire::test(HerbStatsProgress::class)
            ->assertSee('Berechne 2 / 8')
            ->assertSee('25 %')
            ->assertDontSeeText('Wartet auf Worker');
    });

    it('shows a waiting state before a worker picks the run up', function () {
        HerbStatsRegeneration::queue();

        Livewire::test(HerbStatsProgress::class)
            ->assertSee('Wartet auf Worker');
    });

    it('notifies and refreshes the table once the run finishes', function () {
        HerbStatsRegeneration::start(8);

        $progress = Livewire::test(HerbStatsProgress::class)
            ->assertNotDispatched(HerbStatsProgress::FINISHED_EVENT);

        HerbStatsRegeneration::finish();

        $progress->call('refreshProgress')
            ->assertSet('running', false)
            ->assertDispatched(HerbStatsProgress::FINISHED_EVENT)
            ->assertNotified('Lagerprognose neu berechnet');
    });
});
