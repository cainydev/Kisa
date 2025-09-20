<?php

namespace App\Test;

use Closure;
use Livewire\Wireable;

class Magic implements Wireable
{
    private Closure $callback;

    public function __construct(Closure $fn)
    {
        $this->callback = $fn;
    }

    public static function fromLivewire($value): static
    {
        $instance = new static(fn() => "default");
        $instance->__unserialize($value);

        return $instance;
    }

    public function __unserialize(array $data): void
    {
        $this->callback = $data["callback"];
    }

    public function run(...$args)
    {
        return ($this->callback)(...$args);
    }

    public function toLivewire(): array
    {
        return $this->__serialize();
    }

    public function __serialize(): array
    {
        return ["callback" => $this->callback];
    }
}
