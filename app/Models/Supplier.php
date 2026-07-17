<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function bioInspector(): BelongsTo
    {
        return $this->belongsTo(BioInspector::class);
    }

    public function herbs(): HasMany
    {
        return $this->hasMany(Herb::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Resolve the certificate that covers the given date. When multiple
     * certificates are valid, the one with the latest validity start wins.
     */
    public function certificateForDate(DateTimeInterface $date): ?Certificate
    {
        return $this->certificates
            ->filter(fn (Certificate $certificate): bool => $certificate->coversDate($date))
            ->sortByDesc('valid_from')
            ->first();
    }
}
