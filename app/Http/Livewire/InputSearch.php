<?php

namespace App\Http\Livewire;

use Exception;
use Illuminate\Support\Collection;
use Livewire\Component;

class InputSearch extends Component
{
    public Collection $c;
    public String $event;
    public String $query;
    public String $attr;

    public function mount(Collection $c, String $attr, String $event = "selected")
    {
        $this->c = $c;
        $this->event = $event;
        $this->attr = $attr;
        $this->query = "";
    }

    public function render()
    {
        if ($this->c->count() > 0) {
            if (!property_exists($this->c->first(), $this->attr))
                throw new Exception('Attribute ' . $this->attr . ' not found on class ' . $this->c->first()::class);

            $filtered = $this->c->where($this->attr, 'LIKE', '');

            return view('livewire.input-search', ['list' => $filtered]);
        }
    }
}
