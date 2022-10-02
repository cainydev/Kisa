<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Orchid\Screen\AsSource;

class Product extends Model
{
    use HasFactory, AsSource;

    protected $guarded = [];


    public function getPercentage(Herb $herb)
    {
        if ($this->herbs->contains($herb)) {
            return $this->herbs->find($herb)->pivot->percentage;
        } else {
            return 0.0;
        }
    }

    public function herbs()
    {
        return $this->belongsToMany(Herb::class)
            ->withPivot('percentage');
    }

    public function type()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
