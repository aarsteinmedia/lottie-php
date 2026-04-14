<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Shapes\ShapePath;

function segmentsLengthPool(): PoolFactory {
  static $instance = null;
  if ($instance === null) {
    $instance = new PoolFactory(
      8,
      fn() => [
        'lengths' => [],
        'totalLength' => 0
      ],
      function (ShapePath $element) {
        $length = $element->lengths;

        for ($i = 0; $i < $length; $i++) {
          bezierLengthPool()->release($element->lengths[$i]);
        }

        $element->lengths = [];
      }
    );
  }
  
  return $instance;
}