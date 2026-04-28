<?php

namespace LottiePHP\Utils;

class LocationHref
{
    public static function getHref()
    {
        return self::$_locationHref;
    }

    public static function setHref(string $value)
    {
        self::$_locationHref = $value;
    }
    private static $_locationHref = '';
}
