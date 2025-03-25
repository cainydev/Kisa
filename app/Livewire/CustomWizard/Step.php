<?php

namespace App\Livewire\CustomWizard;

use Closure;
use Filament\Forms\Components\Wizard\Step as FilamentStep;

class Step extends FilamentStep
{
    protected string $view = 'livewire.custom-wizard.step';

    protected bool $isCompleted = false;
    protected bool|Closure|null $completedWhen = null;

    public function completedWhen(bool|Closure $condition = true): static
    {
        $this->completedWhen = $condition;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize state during setup
        $this->afterStateHydrated(function (Step $step, $state) {
            $this->isCompleted = $this->isCompleted();
        });

        // Listen for child form field updates
        $this->registerListeners([
            'stepField::updated' => [
                function () {
                    $this->isCompleted = $this->isCompleted();
                },
            ],
        ]);
    }

    public function isCompleted(): bool
    {
        return (bool)$this->evaluate($this->completedWhen, ['state' => $this->getState()]);
    }
}
