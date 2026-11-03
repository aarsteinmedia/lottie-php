<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\General;
use LottiePHP\Utils\Helpers\Resolution;

function bezierLengthPool(): PoolFactory
{
    static $instance = null;
    if (null === $instance) {
        $instance = new PoolFactory(
            8,
            fn () => [
                'addedLength' => 0,
                'lengths' => General::createTypedArray(ArrayType::Float32, Resolution::getDefaultCurveSegments()),
                'percents' => General::createTypedArray(ArrayType::Float32, Resolution::getDefaultCurveSegments()),
            ]
        );
    }

    return $instance;
}
