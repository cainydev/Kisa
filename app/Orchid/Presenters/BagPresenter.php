<?php

namespace App\Orchid\Presenters;

use Laravel\Scout\Builder;
use Orchid\Screen\Contracts\Searchable;
use Orchid\Support\Presenter;

class BagPresenter extends Presenter implements Searchable
{
    public function label(): string
    {
        return 'Säcke';
    }

    public function title(): string
    {
        return $this->entity->herb->name.' '.$this->entity->specification;
    }

    public function subTitle(): string
    {
        $bottles = '';
        foreach ($this->entity->ingredients as $ing) {
            $bottles .= 'Abfüllung vom '.$ing->position->bottle->date->format('d.m.Y').', ';
        }
        $bottles = substr($bottles, 0, strlen($bottles) - 2);

        return 'Charge '.$this->entity->charge.' verwendet in: '.$bottles;
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
