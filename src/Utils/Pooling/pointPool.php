<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\General;

function pointPool(): PoolFactory
{
    static $instance = null;
    if (null === $instance) {
        $instance = new PoolFactory(8, fn () => General::createTypedArray(ArrayType::Float32, 2));
    }

    return $instance;
}
