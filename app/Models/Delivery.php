<?php

namespace App\Models;

use App\Orchid\Presenters\DeliveryPresenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Orchid\Screen\AsSource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Delivery extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, AsSource, Searchable;

    protected $with = ['bags'];

    protected $guarded = [];

    protected $casts = [
        'delivered_date' => 'date:Y-m-d',
        'bio_inspection' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invoice')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();

        $this->addMediaCollection('deliveryNote')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();

        $this->addMediaCollection('certificate')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->performOnCollections();
    }

    public function presenter()
    {
        return new DeliveryPresenter($this);
    }

    public function toSearchableArray()
    {
        $bags = '';
        foreach ($this->bags as $bag) {
            $bags .= $bag->herb->name . '-' . $bag->getSizeInKilo() . ', ';
        }
        $bags = substr($bags, 0, strlen($bags) - 2);

        return [
            'id' => $this->id,
            'supplier' => $this->supplier->shortname,
            'date' => $this->delivered_date->format('d.m.Y - d.m.y'),
            'bags' => $bags,
        ];
    }

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
