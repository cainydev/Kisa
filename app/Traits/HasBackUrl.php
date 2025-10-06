<?php

namespace App\Traits;

trait HasBackUrl
{
    public ?string $backUrl = null;

    public function mount($record = null): void
    {
        $this->backUrl = url()->previous();
        parent::mount($record);
    }
}
