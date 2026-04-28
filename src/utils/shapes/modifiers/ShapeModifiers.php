<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\Modifier;
use LottiePHP\Utils\Enums\ShapeType;

class ShapeModifiers
{
    /**
     * @var array<string, class-string<ShapeModifier>>
     */
    public static function getModifier(
        ShapeType|string $nm,
        ?BaseElement $_elem = null,
        ?array $_data = null
    ) {
        $key = $nm instanceof ShapeType ? $nm->value : $nm;

        if (!isset(self::$Modifiers[$key])) {
            throw new \Error('Invalid modifier: '.$key);
        }

        /** @var class-string<ShapeModifier> $class */
        $class = self::$Modifiers[$key];

        return new $class();
    }

    /**
     * @param class-string<ShapeModifier> $factory
     */
    public static function registerModifier(Modifier $nm, string $factory): void
    {
        $key = $nm->value;
        self::$Modifiers[$key] = self::$Modifiers[$key] ?? $factory;
    }

    /** @var array<string, class-string<ShapeModifier>> */
    private static array $Modifiers = [];
}
