<?php

namespace App\Labels;

use Illuminate\Database\Eloquent\Model;

interface LabelTemplate
{
    /**
     * Stable string key, persisted on Label rows.
     */
    public function key(): string;

    /**
     * Human-readable name shown in selects and UIs.
     */
    public function name(): string;

    /**
     * Eloquent classes this template can be applied to.
     *
     * @return array<class-string<Model>>
     */
    public function subjects(): array;

    /**
     * Refine subject scope. Default: any instance of a class in subjects().
     * Override for product-specific templates etc.
     */
    public function appliesTo(Model $entity): bool;

    /**
     * Physical label size in millimeters.
     *
     * @return array{width_mm: int, height_mm: int}
     */
    public function dimensions(): array;

    /**
     * Named pages: pageKey => Blade view name.
     *
     * @return array<string, string>
     */
    public function pages(): array;

    /**
     * Parameter schema.
     *
     * @return Param[]
     */
    public function parameters(): array;
}
