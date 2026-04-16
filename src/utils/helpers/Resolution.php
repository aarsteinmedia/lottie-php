<?php

namespace LottiePHP\Utils\Helpers;

class Resolution
{
    private static $curveSegments = 150;

    public static function setDefaultCurveSegments(int $value)
    {
        self::$curveSegments = $value;
    }
    public static function getDefaultCurveSegments()
    {
        return self::$curveSegments;
    }

    private static $shouldRoundValues = false;

    public static function getShouldRoundValues()
    {
        return self::$shouldRoundValues;
    }

    public static function setShouldRoundValues(bool $value)
    {
        self::$shouldRoundValues = $value;
    }

    public static function setQuality(int|string $value)
    {
        if (gettype($value) === 'string') {
            switch ($value) {
                case 'high': {
                    self::setDefaultCurveSegments(200);
                    break;
                }
                default:
                case 'medium': {
                    self::setDefaultCurveSegments(50);
                    break;
                }
                case 'low': {
                    self::setDefaultCurveSegments(10);
                    break;
                }
            }
        } elseif (!is_nan($value) && $value > 1) {
            self::setDefaultCurveSegments($value);
        }

        self::setShouldRoundValues(self::getDefaultCurveSegments() < 50);
    }

}
