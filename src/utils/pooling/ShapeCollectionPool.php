<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Shapes\ShapeCollection;

use function LottiePHP\Utils\createSizedArray;

class ShapeCollectionPool
{
    private $_length = 0;
    private $_maxLength = 4;
    private ?array $pool;

    public static function newShapeCollection()
    {
        if (!self::$pool) {
            self::$pool = createSizedArray(self::$_maxLength);
        }
        $shapeCollection = null;

        if (self::$_length) {
            self::$_length -= 1;
            $shapeCollection = self::$pool[self::$_length];
        } else {
            $shapeCollection = new ShapeCollection();
        }

        return $shapeCollection;
    }

    public static function releaseShape(ShapeCollection $shapeCollection)
    {

        if (!self::$pool) {
            self::$pool = createSizedArray(self::$_maxLength);
        }

        for ($i = 0; $i < $shapeCollection->_length; $i++) {
            ShapePool::release($shapeCollection->shapes[$i]);
        }
        $shapeCollection->_length = 0;

        if (self::$_length === self::$_maxLength) {
            self::$pool = double(self::$pool);
            self::$_maxLength *= 2;
        }

        self::$pool[self::$_length] = $shapeCollection;

        self::$_length++;
    }

}
