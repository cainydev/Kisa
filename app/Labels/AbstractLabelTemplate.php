<?php

namespace App\Labels;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractLabelTemplate implements LabelTemplate
{
    public function appliesTo(Model $entity): bool
    {
        foreach ($this->subjects() as $class) {
            if ($entity instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when this template can render against just an entity, with no Label row supplying overrides.
     * That holds when every required Param has either an auto or literal default.
     */
    public function canRenderBare(): bool
    {
        foreach ($this->parameters() as $param) {
            if ($param->isRequired() && ! $param->hasDefault()) {
                return false;
            }
        }

        return true;
    }
}
