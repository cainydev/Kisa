<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Certificate extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date:Y-m-d',
            'valid_until' => 'date:Y-m-d',
            'issued_at' => 'date:Y-m-d',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('small')
            ->width(595)
            ->height(842)
            ->performOnCollections('document');

        $this->addMediaConversion('big')
            ->width(1240)
            ->height(1754)
            ->performOnCollections('document');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function coversDate(DateTimeInterface $date): bool
    {
        return $this->valid_from !== null
            && $this->valid_until !== null
            && $this->valid_from->lessThanOrEqualTo($date)
            && $this->valid_until->greaterThanOrEqualTo($date);
    }
}
