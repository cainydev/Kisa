<?php

namespace App\Models;

use App\Labels\ParamType;
use App\Labels\TemplateRegistry;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Label extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'parameters' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Label $label) {
            if (! $label->parent_id) {
                return;
            }
            $seen = $label->id ? [$label->id] : [];
            $cursor = static::find($label->parent_id);
            while ($cursor) {
                if (in_array($cursor->id, $seen, true)) {
                    throw new DomainException('Label hierarchy cycle detected');
                }
                $seen[] = $cursor->id;
                $cursor = $cursor->parent;
            }
        });
    }

    public function registerMediaCollections(): void
    {
        if (! app()->bound(TemplateRegistry::class)) {
            return;
        }

        $mediaKeys = [];
        foreach (app(TemplateRegistry::class)->all() as $template) {
            foreach ($template->parameters() as $param) {
                if (in_array($param->type(), [ParamType::Image, ParamType::Font], true)) {
                    $mediaKeys[$param->key()] = true;
                }
            }
        }

        foreach (array_keys($mediaKeys) as $key) {
            $this->addMediaCollection("param_{$key}")->singleFile();
        }
    }

    public function labelable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return iterable<self>
     */
    public function ancestorChain(): iterable
    {
        $cursor = $this;
        $seen = [];
        while ($cursor && ! in_array($cursor->id, $seen, true)) {
            $seen[] = $cursor->id;
            yield $cursor;
            $cursor = $cursor->parent;
        }
    }

    public function isAbstract(): bool
    {
        return $this->labelable_type === null || $this->labelable_id === null;
    }

    /**
     * True if this label or any ancestor has stored a value (parameter or
     * media file) for the given parameter key. Used to decide whether a
     * shared parameter still needs a field on this child label.
     */
    public function hasAncestorValue(string $key, ParamType $type): bool
    {
        $isMedia = in_array($type, [ParamType::Image, ParamType::Font], true);
        foreach ($this->ancestorChain() as $ancestor) {
            if ($isMedia) {
                if ($ancestor->getMedia("param_{$key}")->isNotEmpty()) {
                    return true;
                }

                continue;
            }
            $params = $ancestor->parameters ?? [];
            if (array_key_exists($key, $params) && $params[$key] !== null && $params[$key] !== '') {
                return true;
            }
        }

        return false;
    }
}
