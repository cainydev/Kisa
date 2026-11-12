<?php

namespace App\Livewire;

use App\Support\Stats\HerbStatsRegeneration;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Progress bar shown in place of the reorder table's "Neu berechnen" button
 * while herb stats are regenerating. Polls on its own so the (expensive)
 * reorder table isn't re-rendered every second.
 */
class HerbStatsProgress extends Component
{
    public const string FINISHED_EVENT = 'herb-stats-regenerated';

    public int $processed = 0;

    public int $total = 0;

    public bool $running = true;

    public function mount(): void
    {
        $this->refreshProgress();
    }

    public function refreshProgress(): void
    {
        $current = HerbStatsRegeneration::current();

        if ($current === null) {
            if ($this->running) {
                $this->running = false;

                Notification::make()
                    ->title('Lagerprognose neu berechnet')
                    ->success()
                    ->send();

                $this->dispatch(self::FINISHED_EVENT);
            }

            return;
        }

        $this->processed = $current['processed'];
        $this->total = $current['total'];
    }

    public function render(): View
    {
        return view('livewire.herb-stats-progress', [
            'percentage' => $this->total > 0 ? (int) floor($this->processed / $this->total * 100) : null,
        ]);
    }
}
