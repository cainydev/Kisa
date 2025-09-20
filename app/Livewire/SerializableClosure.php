<?php

namespace App\Livewire;

use Closure;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SerializableClosure extends Component
{
    #[Locked]
    public Closure $magic;

    public string $input = '';

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <script>
                axios.get('/api/herb/autocomplete/myquery').then(function (response) {
                    console.log(response);
                }).catch(function (error) {
                    console.log(error);
                })
            </script>
            <input wire:model.live="input">
            <h1>{{ $magic($input)->implode(', ') }}</h1>
        </div>
        HTML;
    }
}
