<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class SimpleWizard extends Component
{
    #[Modelable]
    public int $currentStep;

    #[Locked]
    public array $steps = [];

    #[Reactive, Locked]
    public array $completedSteps = [];

    public function goToNextStep(): void
    {
        $nextStepIndex = $this->getStepIndex($this->currentStep) + 1;

        if ($nextStepIndex >= count($this->steps)) {
            return;
        }

        $this->goToStep($this->steps[$nextStepIndex]['key']);
    }

    public function getStepIndex(int $stepKey): int|string
    {
        foreach ($this->steps as $index => $step) {
            if ($step['key'] === $stepKey) {
                return $index;
            }
        }
        return 0;
    }

    public function goToStep(int $stepKey): void
    {
        $this->currentStep = $stepKey;
    }

    public function goToPreviousStep(): void
    {
        $previousStepIndex = $this->getStepIndex($this->currentStep) - 1;

        if ($previousStepIndex < 0) {
            return;
        }

        $this->goToStep($this->steps[$previousStepIndex]['key']);
    }

    public function isStepCompleted(int $stepKey): bool
    {
        return in_array($stepKey, $this->completedSteps);
    }

    public function isFirstStep(): bool
    {
        return $this->getStepIndex($this->currentStep) <= 0;
    }

    public function isLastStep(): bool
    {
        return $this->getStepIndex($this->currentStep) + 1 >= count($this->steps);
    }

    public function render(): View
    {
        return view('livewire.simple-wizard');
    }
}
