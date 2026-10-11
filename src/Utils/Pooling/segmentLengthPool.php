<?php

namespace LottiePHP\Utils\Pooling;

function segmentsLengthPool(): PoolFactory
{
    static $instance = null;
    if (null === $instance) {
        $instance = new PoolFactory(
            8,
            fn () => [
                'lengths' => [],
                'totalLength' => 0,
            ],
            // Elements are arrays, so take them by reference for the reset to reach the pool
            static function (array &$element) {
                foreach ($element['lengths'] as $lengthData) {
                    bezierLengthPool()->release($lengthData);
                }

                $element['lengths'] = [];
                $element['totalLength'] = 0;
            }
        );
    }

    return $instance;
}
