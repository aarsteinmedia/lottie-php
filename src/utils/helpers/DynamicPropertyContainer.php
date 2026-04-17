<?php

namespace LottiePHP\Utils\Helpers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Properties\BaseProperty;

abstract class DynamicPropertyContainer
{
    public ?bool $_isAnimated;
    public ?bool $_mdf;
    public ?DynamicPropertyContainer $container;
    public ?array $data;
    public $dynamicProperties = [];
    public ?bool $hd;
    public ?array $keyframes;
    public ?int $propertyIndex;
    public null|PropType|false $propType;

    public function addDynamicProperty(DynamicPropertyContainer $prop)
    {
        if (in_array($prop, $this->dynamicProperties)) {
            return;
        }
        array_push($this->dynamicProperties, $prop);
        $this->container['addDynamicProperty']($this);
        $this->_isAnimated = true;
    }

    public function getValue(?bool $_flag)
    {
        throw new \Error(get_class($this) . ": Method getValue is not implemented");
    }

    public function initDynamicPropertyContainer(DynamicPropertyContainer $container)
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

        for ($i = 0; $i < $length; $i++) {
            $this->dynamicProperties[$i]['getValue']();
            if ($this->dynamicProperties[$i]['_mdf']) {
                $this->_mdf = true;
            }
        }

        return 0; // For type compability
    }

    public function setGroupProperty($_propertyInterface)
    {
        // Pass through
        // throw new Error('Method is not implemented')
    }
}
