<?php

namespace LottiePHP\Effects;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;

abstract class EffectZero
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
}
