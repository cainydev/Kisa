<?php

namespace App\Http\Traits;

use App\Models\TableSetting;

trait WithSorting
{
    public string $sortBy = 'id';

    public string $direction = 'asc';

    protected $queryStringWithSorting = [
        'sortBy' => ['except' => 'id'],
        'direction' => ['except' => 'asc'],
    ];

    public function initializeWithSorting()
    {
        $this->listeners = array_merge($this->listeners, ['sortBy']);
    }

    public function resetSort()
    {
        $this->sortBy = 'id';
        $this->direction = 'asc';
    }

    public function sortBy(string $direction, string $sortBy = 'id')
    {
        $this->resetPage();
        $this->direction = $direction;
        $this->sortBy = $sortBy;
    }

    public function addSortToQuery($query)
    {
        $table = $query->from;

        $tableSettings = TableSetting::firstWhere('tablename', $table);

        if ($tableSettings != null) {
            if ($tableSettings->isSortable($this->sortBy)) {
                $this->sortBy($this->direction, $this->sortBy);
            } else {
                $this->resetSort();
            }
        } else {
            $this->resetSort();
        }

        return $query->orderBy($this->sortBy, $this->direction);
    }
}
