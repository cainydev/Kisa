<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Orchid\Screen\AsSource;

class Delivery extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, AsSource;

    protected $with = ['bags'];

    protected $guarded = [];

    protected $casts = [
        'delivered_date' => 'date:Y-m-d',
        'bio_inspection' => 'array',
    ];

    public function addBag(Bag $bag)
    {
        $this->bags->push($bag);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bags()
    {
        return $this->hasMany(Bag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
