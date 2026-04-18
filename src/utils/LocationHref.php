<?php

namespace LottiePHP\Utils;

class LocationHref
{
    private static $_locationHref = '';

    public static function setHref(string $value)
    {
        self::$_locationHref = $value;
    }

    public static function getHref()
    {
        return self::$_locationHref;
    }
}
