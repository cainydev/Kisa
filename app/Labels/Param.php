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

    /**
     * @var array<string, string>|null
     */
    private ?array $selectOptions = null;

    private bool $hyphenate = false;

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

    public function boolean(): self
    {
        $this->type = ParamType::Boolean;

        return $this;
    }

    /**
     * @param  array<string, string>  $options  Map of stored value => human label.
     */
    public function select(array $options): self
    {
        $this->type = ParamType::Select;
        $this->selectOptions = $options;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function selectOptions(): array
    {
        return $this->selectOptions ?? [];
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

    /**
     * Run the resolved value through a German hyphenator (Knuth–Liang via
     * vanderlee/syllable) so the rendered Blade gets soft hyphens (U+00AD)
     * inserted at legal break points. Server-side equivalent of CSS
     * `hyphens: auto`, used because Chromium's built-in hyphenation depends
     * on a runtime-downloaded component that isn't always available.
     *
     * Only meaningful for string-typed params.
     */
    public function hyphenate(): self
    {
        $this->hyphenate = true;

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

    public function isHyphenated(): bool
    {
        return $this->hyphenate;
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
