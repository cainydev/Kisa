<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function inspector()
    {
        return $this->belongsTo(BioInspector::class);
    }

    public function herbs()
    {
        return $this->hasMany(Herb::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}
