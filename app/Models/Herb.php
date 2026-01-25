<?php

namespace App\Models;

use App\Support\Stats\HerbStats;
use App\Traits\CachedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// AbstractStatistics is used via the trait

// Still needed for estimatedDepletionDate

class Herb extends Model
{
    use HasFactory, CachedAttributes;

    protected $guarded = [];

    public function toSearchableArray(): array
    {
        $prods = $this->products->pluck('name')->implode(', ');
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullname' => $this->fullname,
            'prods' => $prods,
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function bags(): HasMany
    {
        return $this->hasMany(Bag::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    protected function currentStock(): Attribute
    {
        return Attribute::make(get: fn() => HerbStats::for($this)->currentStock());
    }
}
