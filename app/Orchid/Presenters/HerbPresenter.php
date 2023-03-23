<?php

namespace App\Orchid\Presenters;

use Laravel\Scout\Builder;
use Orchid\Screen\Contracts\Searchable;
use Orchid\Support\Presenter;

class HerbPresenter extends Presenter implements Searchable
{
    public function label(): string
    {
        return 'Rohstoffe';
    }

    public function title(): string
    {
        return $this->entity->name;
    }

    public function subTitle(): string
    {
        $prods = '';
        foreach ($this->entity->products as $prod) {
            $prods .= $prod->name.', ';
        }
        $prods = substr($prods, 0, strlen($prods) - 2);

        return $this->entity->fullname.' kommt vor in: '.$prods;
    }

    public function url(): string
    {
        return route('platform.herbs.edit', $this->entity);
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
