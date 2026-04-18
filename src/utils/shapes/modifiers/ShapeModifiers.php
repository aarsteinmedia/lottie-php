<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;

class ShapeModifiers
{
    private static $Modifiers = [];

    public static function getModifier(
        string $nm,
        ?BaseElement $_elem = null,
        ?array $_data = null
    ) {
        if (!self::$Modifiers[$nm]) {
            throw new \Error('Invalid modifier');
        }

        return new self::$Modifiers[$nm]();
    }

    public static function registerModifier(string $nm, array $factory)
    {
        self::$Modifiers[$nm] = self::$Modifiers[$nm] ?? $factory;
    }
}
