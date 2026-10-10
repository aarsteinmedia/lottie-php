<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\EffectOne;
use LottiePHP\Effects\GroupEffect;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\MultiDimensionalProperty;
use LottiePHP\Utils\Properties\ValueProperty;

/**
 * Read effect control values from GroupEffect (object or legacy array shape).
 */
final class EffectProp
{
    public static function mdf(GroupEffect $manager, int $index): bool
    {
        $prop = self::property($manager, $index);

        return (bool) ($prop->_mdf ?? false);
    }

    public static function property(GroupEffect $manager, int $index): BaseProperty|MultiDimensionalProperty|ValueProperty|null
    {
        $eff = $manager->effectElements[$index] ?? null;

        if ($eff instanceof EffectOne) {
            return $eff->p;
        }

        if (!is_array($eff)) {
            return null;
        }

        $p = $eff['p'] ?? null;

        if ($p instanceof BaseProperty || $p instanceof MultiDimensionalProperty || $p instanceof ValueProperty) {
            return $p;
        }

        return null;
    }

    public static function value(GroupEffect $manager, int $index, mixed $default = null): mixed
    {
        $prop = self::property($manager, $index);

        if (null !== $prop) {
            return $prop->v ?? $default;
        }

        $eff = $manager->effectElements[$index] ?? null;

        if (is_array($eff)) {
            return $eff['p']['v'] ?? $default;
        }

        return $default;
    }
}
