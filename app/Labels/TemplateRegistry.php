<?php

namespace App\Labels;

use InvalidArgumentException;

class TemplateRegistry
{
    /** @var array<string, LabelTemplate> */
    private array $templates = [];

    /**
     * @param  array<class-string<LabelTemplate>>  $classes
     */
    public function __construct(array $classes)
    {
        foreach ($classes as $class) {
            $instance = new $class;
            if (! $instance instanceof LabelTemplate) {
                throw new InvalidArgumentException("{$class} does not implement LabelTemplate");
            }
            if (isset($this->templates[$instance->key()])) {
                throw new InvalidArgumentException("Duplicate template key: {$instance->key()}");
            }
            $this->templates[$instance->key()] = $instance;
        }
    }

    public function get(string $key): LabelTemplate
    {
        if (! isset($this->templates[$key])) {
            throw new InvalidArgumentException("Unknown template key: {$key}");
        }

        return $this->templates[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->templates[$key]);
    }

    /**
     * @return array<string, LabelTemplate>
     */
    public function all(): array
    {
        return $this->templates;
    }

    /**
     * @return array<string, string> key => human-readable name
     */
    public function options(): array
    {
        return array_map(fn (LabelTemplate $t) => $t->name(), $this->templates);
    }
}
