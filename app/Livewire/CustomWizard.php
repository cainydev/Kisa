<?php

namespace App\Livewire;

use App\Livewire\CustomWizard\Step;
use Closure;
use Filament\Schemas\Components\Wizard;
use Filament\Support\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class CustomWizard extends Wizard
{
    protected string $view = 'livewire.custom-wizard.wizard';

    /** @var string|Htmlable|Component|Closure|null */
    protected string|Htmlable|Component|Closure|null $footerContent = null;

    /**
     * @param array<Step>|Closure $steps
     */
    public static function make(array|Closure $steps = []): static
    {
        $static = app(static::class, ['steps' => $steps]);
        $static->configure();

        return $static;
    }

    /**
     * Keep parent initialization for internal container setup.
     *
     * @param array<Step>|Closure $steps
     */
    public function steps(array|Closure $steps): static
    {
        return parent::steps($steps);
    }

    /**
     * Accept arbitrary content (string, Htmlable, Closure returning content, or a schema Component).
     */
    public function footerContent(string|Htmlable|Component|Closure|null $content): static
    {
        $this->footerContent = $content;

        return $this;
    }

    /**
     * Resolve footer content.
     */
    public function getFooterContent(): mixed
    {
        return $this->evaluate($this->footerContent);
    }
}
