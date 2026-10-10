<?php

namespace LottiePHP\Effects;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;

abstract class EffectOne
{
    public ?BaseProperty $p;

    public function __construct(
        array $data,
        BaseElement|BaseProperty $elem,
        GroupEffect $container
    ) {
        $this->p = PropertyFactory::getProp(
            $elem,
            $data['v'],
            1,
            0,
            $container
        );
    }
}
