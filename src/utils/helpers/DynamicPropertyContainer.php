<?php

namespace LottiePHP\Utils\Helpers;

use LottiePHP\Utils\Enums\PropType;

abstract class DynamicPropertyContainer implements \ArrayAccess
{
    public ?bool $_isAnimated;
    public ?bool $_mdf = null;

    /**
     * In the original TS implementation this is a structural type (“anything with addDynamicProperty”).
     * In PHP we keep it loose to avoid brittle cross-hierarchy typing in this port.
     */
    public mixed $container = null;
    public ?array $data;
    public $dynamicProperties = [];
    public ?bool $hd;
    public ?array $keyframes;
    public ?int $propertyIndex;
    public false|PropType|null $propType;

    public function addDynamicProperty(DynamicPropertyContainer $prop)
    {
        if (in_array($prop, $this->dynamicProperties)) {
            return;
        }
        array_push($this->dynamicProperties, $prop);
        if (is_object($this->container) && method_exists($this->container, 'addDynamicProperty')) {
            $this->container->addDynamicProperty($this);
        } elseif (is_array($this->container) && isset($this->container['addDynamicProperty']) && is_callable($this->container['addDynamicProperty'])) {
            ($this->container['addDynamicProperty'])($this);
        }
        $this->_isAnimated = true;
    }

    public function getValue(?bool $_flag)
    {
        throw new \Error(get_class($this).': Method getValue is not implemented');
    }

    public function initDynamicPropertyContainer(mixed $container)
    {
        $this->container = $container;
        $this->dynamicProperties = [];
        $this->_mdf = false;
        $this->_isAnimated = false;
    }

    public function iterateDynamicProperties(): int
    {
        $this->_mdf = false;
        $length = count($this->dynamicProperties);

        for ($i = 0; $i < $length; ++$i) {
            $this->dynamicProperties[$i]->getValue();
            if ($this->dynamicProperties[$i]->_mdf) {
                $this->_mdf = true;
            }
        }

        return 0; // For type compability
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

    public function setGroupProperty($_propertyInterface)
    {
        // Pass through
        // throw new Error('Method is not implemented')
    }
}
