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
        
      }
    );
  }
  return $instance;


  // new PoolFactory(
  //   8,
  //   () => ({
  //     lengths: [],
  //     totalLength: 0,
  //   }),
  //   (element: PoolElement) => {

  //     if (!isShapePath(element)) {
  //       return
  //     }

  //     const { length } = element.lengths

  //     for (let i = 0; i < length; i++) {
  //       bezierLengthPool.release(element.lengths[i] as PoolElement)
  //     }
  //     element.lengths.length = 0
  //   }
}