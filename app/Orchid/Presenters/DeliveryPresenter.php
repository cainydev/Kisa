<?php

namespace App\Orchid\Presenters;

use Laravel\Scout\Builder;
use Orchid\Screen\Contracts\Searchable;
use Orchid\Support\Presenter;

class DeliveryPresenter extends Presenter implements Searchable
{
    /**
     * @return string
     */
    public function label(): string
    {
        return 'Lieferungen';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Galke, ' . $this->entity->delivered_date->format('d.m.y');
    }

    /**
     * @return string
     */
    public function subTitle(): string
    {
        $bags = '';
        foreach ($this->entity->bags as $bag) {
            $bags .= $bag->herb->name . '-' . $bag->getSizeInKilo() . ', ';
        }
        $bags = substr($bags, 0, strlen($bags) - 2);

        return 'Säcke: ' . $bags;
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return route('platform.deliveries.edit', $this->entity);
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
