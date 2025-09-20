<?php

namespace App\Livewire\CustomWizard;

use Closure;

class Step extends \Filament\Schemas\Components\Wizard\Step
{
    protected string $view = 'livewire.custom-wizard.step';

    protected ?Closure $completedWhen = null;

    /**
     * Define a condition that returns true when the step should be considered completed.
     *
     * The closure receives:
     *  - state: the step's current state value (if any)
     *  - step: the step instance
     */
    public function completedWhen(?Closure $callback): static
    {
        $this->completedWhen = $callback;

        return $this;
    }

    public function isCompleted(): bool
    {
        if (!$this->completedWhen) {
            return false;
        }

        // getState() should return the step's (possibly scalar) state value.
        $state = method_exists($this, 'getState') ? $this->getState() : null;

        return (bool)$this->evaluate($this->completedWhen, [
            'state' => $state,
            'step' => $this,
        ]);
    }
}
