<?php

namespace App\Models;

use App\Enums\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

class BioInspector extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'country' => Country::class,
        ];
    }

    /**
     * The control body's name with its control code rendered as a badge, as
     * HTML. Used for Filament option labels that opt into allowHtml().
     */
    public function badgedLabel(): HtmlString
    {
        $code = e($this->label);

        $badge = '<span style="display:inline-block;padding:0.05rem 0.4rem;border-radius:0.375rem;'
            .'font-family:ui-monospace,monospace;font-size:0.75rem;line-height:1.25rem;'
            .'background-color:rgb(var(--gray-100));color:rgb(var(--gray-700));'
            .'border:1px solid rgb(var(--gray-200));white-space:nowrap;">'.$code.'</span>';

        return new HtmlString(
            '<span style="display:inline-flex;align-items:center;gap:0.5rem;">'
            .$badge.'<span>'.e($this->company).'</span></span>'
        );
    }

    /**
     * Returns all suppliers supervised by the bioInspector
     *
     * @return HasMany The relationship
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Returns all certificates issued under this control body.
     *
     * @return HasMany The relationship
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
