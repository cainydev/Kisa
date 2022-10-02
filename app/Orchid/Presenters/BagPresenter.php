<?php

namespace App\Orchid\Presenters;

use Laravel\Scout\Builder;
use Orchid\Screen\Contracts\Searchable;
use Orchid\Support\Presenter;

class BagPresenter extends Presenter implements Searchable
{
    /**
     * @return string
     */
    public function label(): string
    {
        return 'Säcke';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->entity->herb->name . ' ' . $this->entity->specification;
    }

    /**
     * @return string
     */
    public function subTitle(): string
    {
        $bottles = '';
        foreach ($this->entity->ingredients as $ing) {
            $bottles .= 'Abfüllung vom ' . $ing->position->bottle->date->format('d.m.Y') . ', ';
        }
        $bottles = substr($bottles, 0, strlen($bottles) - 2);

        return 'Charge ' . $this->entity->charge . ' verwendet in: ' . $bottles;
    }

    /**
     * @return string
     */
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
