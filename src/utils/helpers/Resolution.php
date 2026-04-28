<?php

namespace LottiePHP\Utils\Helpers;

class Resolution
{
    public static function getDefaultCurveSegments()
    {
        return self::$curveSegments;
    }

    public static function getShouldRoundValues()
    {
        return self::$shouldRoundValues;
    }

    public static function setDefaultCurveSegments(int $value)
    {
        self::$curveSegments = $value;
    }

    public static function setQuality(int|string $value)
    {
        if ('string' === gettype($value)) {
            switch ($value) {
                case 'high':
                    self::setDefaultCurveSegments(200);

                    break;

                default:
                case 'medium':
                    self::setDefaultCurveSegments(50);

                    break;

                case 'low':
                    self::setDefaultCurveSegments(10);

                    break;
            }
        } elseif (!is_nan($value) && $value > 1) {
            self::setDefaultCurveSegments($value);
        }

        self::setShouldRoundValues(self::getDefaultCurveSegments() < 50);
    }

    public static function setShouldRoundValues(bool $value)
    {
        self::$shouldRoundValues = $value;
    }
    private static $curveSegments = 150;

    private static $shouldRoundValues = false;
}
