<?php

namespace App\Orchid\Presenters;

use Laravel\Scout\Builder;
use Orchid\Screen\Contracts\Searchable;
use Orchid\Support\Presenter;

class BottlePresenter extends Presenter implements Searchable
{
    /**
     * @return string
     */
    public function label(): string
    {
        return 'Abfüllungen';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Abfüllung ' . $this->entity->date->format('d.m.y');
    }

    /**
     * @return string
     */
    public function subTitle(): string
    {
        $prods = '';
        foreach ($this->entity->positions as $pos) {
            $prods .= $pos->variant->product->name . ', ';
        }
        $prods = substr($prods, 0, strlen($prods) - 2);

        return ($this->entity->finished() ? 'Fertig abgefüllt' : 'Nicht fertig abgefüllt') . ': ' . $prods;
    }

    /**
     * @return string
     */
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
     *
     * @return int
     */
    public function perSearchShow(): int
    {
        return 3;
    }

    /**
     * @param string|null $query
     *
     * @return Builder
     */
    public function searchQuery(string $query = null): Builder
    {
        return $this->entity->search($query);
    }
}
