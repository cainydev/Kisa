<?php

namespace App\Orchid\Presenters;

use Laravel\Scout\Builder;
use Orchid\Screen\Contracts\Searchable;
use Orchid\Support\Presenter;

class BottlePresenter extends Presenter implements Searchable
{
    public function label(): string
    {
        return 'Abfüllungen';
    }

    public function title(): string
    {
        return 'Abfüllung '.$this->entity->date->format('d.m.y');
    }

    public function subTitle(): string
    {
        $prods = '';
        foreach ($this->entity->positions as $pos) {
            $prods .= $pos->variant->product->name.', ';
        }
        $prods = substr($prods, 0, strlen($prods) - 2);

        return ($this->entity->finished() ? 'Fertig abgefüllt' : 'Nicht fertig abgefüllt').': '.$prods;
    }

    public function url(): string
    {
        return route('platform.bottle.edit', $this->entity);
    }

    /**
     * @return string
     */
    public function image(): ?string
    {
        return null; // TODO
    }

    /**
     * The number of models to return for show compact search result.
     */
    public function perSearchShow(): int
    {
        return 3;
    }

    public function searchQuery(string $query = null): Builder
    {
        return $this->entity->search($query);
    }
}
