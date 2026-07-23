<?php

namespace App\Models;

use App\Enums\CertificateActivity;
use App\Enums\ProductCategory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
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
            'activities' => AsEnumCollection::of(CertificateActivity::class),
            'product_categories' => AsEnumCollection::of(ProductCategory::class),
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

    public function bioInspector(): BelongsTo
    {
        return $this->belongsTo(BioInspector::class);
    }

    public function coversDate(DateTimeInterface $date): bool
    {
        return $this->valid_from !== null
            && $this->valid_until !== null
            && $this->valid_from->lessThanOrEqualTo($date)
            && $this->valid_until->greaterThanOrEqualTo($date);
    }

    /**
     * A display-ready view of this certificate, resolving activity/category
     * enums to their German labels. Mirrors the shape frozen into a delivery's
     * certificate snapshot, so both the live create-preview and the frozen
     * edit view render through the same template.
     *
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'certificate_number' => $this->certificate_number,
            'operator_name' => $this->supplier?->company,
            'control_body' => $this->bioInspector?->company,
            'control_body_code' => $this->bioInspector?->label,
            'valid_from' => optional($this->valid_from)->toDateString(),
            'valid_until' => optional($this->valid_until)->toDateString(),
            'issued_at' => optional($this->issued_at)->toDateString(),
            'issued_place' => $this->issued_place,
            'activities' => collect($this->activities ?? [])->map->getLabel()->all(),
            'product_categories' => collect($this->product_categories ?? [])->map->getLabel()->all(),
            'document' => null,
        ];
    }
}
