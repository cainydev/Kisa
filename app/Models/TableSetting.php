<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];

    public function getColumns()
    {
        return $this->options['columns'];
    }

    public function hasColumn(string $column)
    {
        return array_key_exists($column, $this->getColumns());
    }

    public function getColumn(string $column)
    {
        if (! $this->hasColumn($column)) {
            return false;
        }

        return $this->getColumns()[$column];
    }

    public function isSortable(string $column)
    {
        if (! $this->hasColumn($column)) {
            return false;
        }

        if (! array_key_exists('withSort', $this->getColumn($column))) {
            return false;
        }

        return $this->getColumns()[$column]['withSort'];
    }

    public function isPrimary(string $column)
    {
        if (! $this->hasColumn($column)) {
            return false;
        }

        if (! array_key_exists('primary', $this->getColumn($column))) {
            return false;
        }

        return $this->getColumns()[$column]['primary'];
    }

    public function isForeign(string $column)
    {
        if (! $this->hasColumn($column)) {
            return false;
        }

        if (! array_key_exists('foreign', $this->getColumn($column))) {
            return false;
        }

        return $this->getColumns()[$column]['foreign'];
    }
}
