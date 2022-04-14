<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Orchid\Screen\AsSource;

class Supplier extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, AsSource;

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
