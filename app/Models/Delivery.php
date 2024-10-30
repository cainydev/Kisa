<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Delivery extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $with = ['bags'];

    protected $guarded = [];

    protected $casts = [
        'delivered_date' => 'date:Y-m-d',
        'bio_inspection' => 'array',
    ];

    protected function title(): Attribute
    {
        return new Attribute(get: function () {
            return "Lieferung von {$this->supplier->shortname} ({$this->delivered_date->format('d.m.Y')})";
        });
    }

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
        $this->addMediaConversion('small')
            ->width(595)
            ->height(842)
            ->performOnCollections();
        $this->addMediaConversion('big')
            ->width(1240)
            ->height(1754)
            ->performOnCollections();
    }

    public function toSearchableArray()
    {
        $bags = '';
        foreach ($this->bags as $bag) {
            $bags .= $bag->herb->name.'-'.$bag->getSizeInKilo().', ';
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
