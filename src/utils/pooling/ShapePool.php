<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Shapes\ShapePath;

class ShapePool
{
    private static ?PoolFactory $_factory;

    public static function newElement(): ShapePath
    {
        if (!self::$_factory) {
            self::$_factory = new PoolFactory(
                4,
                self::_create(...),
                self::_release(...)
            );
        }

        /** @var ShapePath $el */
        $el = self::$_factory->newElement(...);
        
        return $el;
    }

    public static function release(...$args)
    {
        return self::$_factory->release(...$args);
    }

    public static function clone(ShapePath $shape)
    {
        /** @var ShapePath $cloned */
        $cloned = self::newElement();
        $len = $shape->_length ?? count($shape->v);

        $cloned->setLength($len);
        $cloned->c = $shape->c;

        for ($i = 0; $i < $len; ++$i) {
            $cloned->setTripleAt(
                $shape->v[$i][0] ?? 0,
                $shape->v[$i][1] ?? 0,
                $shape->o[$i][0] ?? 0,
                $shape->o[$i][1] ?? 0,
                $shape->i[$i][0] ?? 0,
                $shape->i[$i][1] ?? 0,
                $i
            );
        }

        return $cloned;
    }

    private static function _create()
    {
        return new ShapePath();
    }

    private static function _release($shapePath)
    {
        if (!$shapePath instanceof ShapePath) {
            return;
        }

        $len = $shapePath->_length;

        for ($i = 0; $i < $len; ++$i) {
            pointPool()->release($shapePath->v[$i]);
            pointPool()->release($shapePath->i[$i]);
            pointPool()->release($shapePath->o[$i]);
            $shapePath->v[$i] = null;
            $shapePath->i[$i] = null;
            $shapePath->o[$i] = null;
        }
        $shapePath->_length = 0;
        $shapePath->c = false;
    }
}

// $ShapePool = {
//   clone,
//   newElement
// }
