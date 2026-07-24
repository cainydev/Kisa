<?php

namespace App\Models;

use App\Enums\CertificateActivity;
use App\Enums\ProductCategory;
use App\Services\Traceability\CertificateSnapshotter;
use App\Support\Traceability\BioInspection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Delivery extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $with = ['bags'];

    protected $guarded = [];

    protected $casts = [
        'delivered_date' => 'date:Y-m-d',
        'bio_inspection' => 'array',
        'certificate_snapshot' => 'array',
    ];

    /**
     * Guards the certificate re-resolve against re-entrancy: the resnapshot
     * itself saves the delivery, which would otherwise fire this hook again.
     */
    protected static bool $resnapshotting = false;

    /**
     * When a delivery's supplier or delivery date changes, any existing
     * certificate snapshot was resolved against the old supplier/date and is
     * now stale. Re-resolve it against the new values so the frozen record can
     * never contradict the delivery it belongs to.
     */
    protected static function booted(): void
    {
        static::updated(function (Delivery $delivery): void {
            if (static::$resnapshotting) {
                return;
            }

            if (! $delivery->wasChanged(['supplier_id', 'delivered_date'])) {
                return;
            }

            static::$resnapshotting = true;

            try {
                app(CertificateSnapshotter::class)->resnapshot($delivery);
            } finally {
                static::$resnapshotting = false;
            }
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

    public function registerMediaConversions(?Media $media = null): void
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

    public function toSearchableArray(): array
    {
        $bags = '';
        foreach ($this->bags as $bag) {
            $bags .= $bag->herb->name.'-'.$bag->charge.'-'.$bag->getSizeInKilo().', ';
        }
        $bags = substr($bags, 0, strlen($bags) - 2);

        return [
            'id' => $this->id,
            'supplier' => $this->supplier->shortname,
            'date' => $this->delivered_date->format('d.m.Y - d.m.y'),
            'bags' => $bags,
        ];
    }

    public function addBag(Bag $bag): void
    {
        $this->bags->push($bag);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * The goods-inbound organic inspection recorded on this delivery, wrapped
     * so its checklist and release state have a single source of truth.
     */
    public function bioInspection(): BioInspection
    {
        return BioInspection::fromArray($this->bio_inspection);
    }

    /**
     * The Öko-Kontrollstellen-Code (e.g. "DE-ÖKO-039") frozen onto this
     * delivery at intake, i.e. the control body that certified these goods on
     * the delivery date. This is the audit-authoritative source — prefer it
     * over the supplier's current control body, which may since have changed.
     */
    public function frozenOekoCode(): ?string
    {
        return $this->certificate_snapshot['control_body_code'] ?? null;
    }

    /**
     * The control body's name frozen onto this delivery at intake.
     */
    public function frozenControlBody(): ?string
    {
        return $this->certificate_snapshot['control_body'] ?? null;
    }

    /**
     * A display-ready view of the certificate frozen onto this delivery at
     * intake, with the stored activity/category codes resolved to their German
     * labels. Returns null when no certificate was snapshotted.
     *
     * @return array<string, mixed>|null
     */
    public function certificateSummary(): ?array
    {
        $snapshot = $this->certificate_snapshot;

        if (empty($snapshot) || empty($snapshot['control_body_code'])) {
            return null;
        }

        $activities = collect($snapshot['activities'] ?? [])
            ->map(fn (string $value): string => CertificateActivity::tryFrom($value)?->getLabel() ?? $value)
            ->all();

        $categories = collect($snapshot['product_categories'] ?? [])
            ->map(fn (string $value): string => ProductCategory::tryFrom($value)?->getLabel() ?? $value)
            ->all();

        return [
            'certificate_number' => $snapshot['certificate_number'] ?? null,
            'operator_name' => $snapshot['operator_name'] ?? null,
            'control_body' => $snapshot['control_body'] ?? null,
            'control_body_code' => $snapshot['control_body_code'] ?? null,
            'valid_from' => $snapshot['valid_from'] ?? null,
            'valid_until' => $snapshot['valid_until'] ?? null,
            'issued_at' => $snapshot['issued_at'] ?? null,
            'issued_place' => $snapshot['issued_place'] ?? null,
            'activities' => $activities,
            'product_categories' => $categories,
            'document' => $this->getFirstMedia('certificate'),
        ];
    }

    public function bags(): HasMany
    {
        return $this->hasMany(Bag::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function title(): Attribute
    {
        return new Attribute(get: function () {
            return "Lieferung von {$this->supplier->shortname} ({$this->delivered_date->format('d.m.Y')})";
        });
    }
}
