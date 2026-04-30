<?php

namespace App\Labels;

use Closure;
use Illuminate\Database\Eloquent\Model;

class Param
{
    private string $key;

    private ParamType $type = ParamType::String;

    private bool $required = false;

    private bool $shared = false;

    private bool $hasLiteralDefault = false;

    private mixed $literalDefault = null;

    private ?Closure $autoResolver = null;

    private ?string $label = null;

    private ?float $rangeMin = null;

    private ?float $rangeMax = null;

    private ?float $rangeStep = null;

    private ?string $rangeSuffix = null;

    private function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function image(): self
    {
        $this->type = ParamType::Image;

        return $this;
    }

    public function string(): self
    {
        $this->type = ParamType::String;

        return $this;
    }

    public function number(): self
    {
        $this->type = ParamType::Number;

        return $this;
    }

    public function color(): self
    {
        $this->type = ParamType::Color;

        return $this;
    }

    public function font(): self
    {
        $this->type = ParamType::Font;

        return $this;
    }

    public function required(): self
    {
        $this->required = true;

        return $this;
    }

    public function shared(): self
    {
        $this->shared = true;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->hasLiteralDefault = true;
        $this->literalDefault = $value;

        return $this;
    }

    public function auto(Closure $resolver): self
    {
        $this->autoResolver = $resolver;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function range(float $min, float $max, float $step = 1, ?string $suffix = null): self
    {
        $this->rangeMin = $min;
        $this->rangeMax = $max;
        $this->rangeStep = $step;
        $this->rangeSuffix = $suffix;

        return $this;
    }

    public function hasRange(): bool
    {
        return $this->rangeMin !== null && $this->rangeMax !== null;
    }

    public function rangeMin(): ?float
    {
        return $this->rangeMin;
    }

    public function rangeMax(): ?float
    {
        return $this->rangeMax;
    }

    public function rangeStep(): ?float
    {
        return $this->rangeStep;
    }

    public function rangeSuffix(): ?string
    {
        return $this->rangeSuffix;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function type(): ParamType
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function hasLiteralDefault(): bool
    {
        return $this->hasLiteralDefault;
    }

    public function hasAutoDefault(): bool
    {
        return $this->autoResolver !== null;
    }

    public function hasDefault(): bool
    {
        return $this->hasLiteralDefault || $this->hasAutoDefault();
    }

    public function literalDefault(): mixed
    {
        return $this->literalDefault;
    }

    public function resolveAuto(?Model $entity): mixed
    {
        if (! $this->autoResolver) {
            return null;
        }

        return ($this->autoResolver)($entity);
    }

    public function humanLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        return ucfirst(preg_replace('/(?<!^)([A-Z])/', ' $1', $this->key));
    }
}
