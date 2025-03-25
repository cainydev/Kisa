<?php

namespace App\Livewire;

use App\Livewire\CustomWizard\Step;
use Closure;
use Filament\Forms\Components\Wizard;
use Illuminate\Contracts\Support\Htmlable;
use function app;

class CustomWizard extends Wizard
{
    protected string|Htmlable|null $footerContent = null;

    protected string $view = 'livewire.custom-wizard.wizard';

    /**
     * @param array<Step> | Closure $steps
     */
    public static function make(array|Closure $steps = []): static
    {
        $static = app(static::class, ['steps' => $steps]);
        $static->configure();

        return $static;
    }

    /**
     * @param array<Step> | Closure $steps
     */
    public function steps(array|Closure $steps): static
    {
        $this->childComponents($steps);

        return $this;
    }

    public function footerContent(string|Htmlable|null $content): static
    {
        $this->footerContent = $content;

        return $this;
    }

    public function getFooterContent(): string|Htmlable|null
    {
        return $this->footerContent;
    }
}
