<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Enums\ArrayType;

use function LottiePHP\Utils\createTypedArray;

function pointPool(): PoolFactory
{
    static $instance = null;
    if ($instance === null) {
        $instance = new PoolFactory(8, fn () => createTypedArray(ArrayType::Float32, 2));
    }
    return $instance;
}
