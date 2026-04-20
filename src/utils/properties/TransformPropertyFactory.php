<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

class TransformPropertyFactory
{
    public static function getTransformProperty(
        BaseElement|DynamicPropertyContainer $elem,
        array $data,
        BaseElement|DynamicPropertyContainer|null $container
    ): TransformProperty {
        return new TransformProperty(
            $elem,
            $data,
            $container
        );
    }
}
