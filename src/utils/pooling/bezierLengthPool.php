<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Helpers\Resolution;

use function LottiePHP\Utils\createTypedArray;

function bezierLengthPool(): PoolFactory {
  static $instance = null;
  if ($instance === null) {
    $instance = new PoolFactory(
      8,
      fn() => [
        'addedLength' => 0,
        'lengths' => createTypedArray(ArrayType::Float32, Resolution::getDefaultCurveSegments()),
        'percents' => createTypedArray(ArrayType::Float32, Resolution::getDefaultCurveSegments())
      ]
    );
  }

  return $instance;
}
