<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];

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
     * Resolve the authoritative certificate covering the given date. When more
     * than one certificate covers the date — e.g. a renewal issued before the
     * previous one expired, or a mid-cycle re-issue that corrects or extends an
     * existing certificate — the most recently issued document supersedes the
     * others. Ties (or a missing issue date) fall back to the latest validity
     * start.
     */
    public function certificateForDate(DateTimeInterface $date): ?Certificate
    {
        return $this->certificates
            ->filter(fn (Certificate $certificate): bool => $certificate->coversDate($date))
            ->sortBy([
                ['issued_at', 'desc'],
                ['valid_from', 'desc'],
            ])
            ->first();
    }

    /**
     * The certificate valid today, i.e. the supplier's currently active
     * certificate. Null when no certificate covers the present date.
     */
    public function currentCertificate(): ?Certificate
    {
        return $this->certificateForDate(now());
    }

    /**
     * The supplier's control body as implied by their currently active
     * certificate. There is no stored control body: a body the supplier
     * cannot back with a valid certificate is not audit-real. Null when no
     * certificate is currently valid.
     */
    public function currentBioInspector(): ?BioInspector
    {
        return $this->currentCertificate()?->bioInspector;
    }
}
