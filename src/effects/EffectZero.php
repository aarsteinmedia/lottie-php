<?php

namespace LottiePHP\Effects;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;

abstract class EffectZero implements \ArrayAccess
{
    public ?ValueProperty $p;

    public function __construct(
        array $data,
        BaseElement|BaseProperty $elem,
        GroupEffect $container
    ) {
        $this->p = PropertyFactory::getProp(
            $elem,
            $data['v'],
            0,
            0,
            $container
        );
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset) || !property_exists($this, $offset)) {
            return null;
        }

        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            return;
        }
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset) || !property_exists($this, $offset)) {
            return;
        }
        $this->{$offset} = null;
    }
}
