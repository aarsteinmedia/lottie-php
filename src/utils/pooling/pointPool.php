<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Enums\ArrayType;

use function LottiePHP\Utils\createTypedArray;

$pointPool = (function () {
    new PoolFactory(8, fn () => createTypedArray(ArrayType::Float32, 2));
})();
