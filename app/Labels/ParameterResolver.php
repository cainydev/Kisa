<?php

namespace App\Labels;

use App\Models\Label;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ParameterResolver
{
    public function __construct(private readonly Hyphenator $hyphenator) {}

    /**
     * Resolve every Param the template declares to a concrete value.
     *
     * Order per parameter:
     *   1. Walk the Label parent chain (if a Label was provided):
     *        a. If the param is an image or font type, look at the Label's "param_<key>" media collection.
     *        b. Otherwise look at $label->parameters[$key].
     *      First non-empty hit wins.
     *   2. Fall back to the template's default:
     *        a. Auto closure called with the entity (or null).
     *        b. Literal default if defined.
     *   3. If still null and the param is required, throw.
     *   4. If the param is `->hyphenate()`, run the resolved string through
     *      a Knuth–Liang hyphenator that injects U+00AD soft hyphens.
     *
     * @return array<string, mixed>
     */
    public function resolve(LabelTemplate $template, ?Label $label, ?Model $entity): array
    {
        $values = [];
        foreach ($template->parameters() as $param) {
            $value = $this->resolveOne($param, $label, $entity);
            if ($param->isHyphenated() && is_string($value) && $value !== '') {
                $value = $this->hyphenator->hyphenate($value);
            }
            $values[$param->key()] = $value;
        }

        return $values;
    }

    private function resolveOne(Param $param, ?Label $label, ?Model $entity): mixed
    {
        $key = $param->key();

        $isMediaParam = in_array($param->type(), [ParamType::Image, ParamType::Font], true);

        if ($label) {
            foreach ($label->ancestorChain() as $ancestor) {
                $hit = $isMediaParam
                    ? $ancestor->getFirstMedia("param_{$key}")
                    : $this->scalarFrom($ancestor, $key);
                if ($hit !== null) {
                    return $hit;
                }
            }
        }

        if ($param->hasAutoDefault()) {
            $auto = $param->resolveAuto($entity);
            if ($auto !== null && $auto !== '') {
                return $auto;
            }
        }

        if ($param->hasLiteralDefault()) {
            return $param->literalDefault();
        }

        if ($param->isRequired()) {
            throw new RuntimeException("Required parameter '{$key}' has no value");
        }

        return null;
    }

    private function scalarFrom(Label $label, string $key): mixed
    {
        $params = $label->parameters ?? [];
        if (! array_key_exists($key, $params)) {
            return null;
        }
        $value = $params[$key];
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
