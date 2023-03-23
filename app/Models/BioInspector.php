<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class BioInspector extends Model
{
    use HasFactory, AsSource;

    protected $guarded = [];

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }
}
