<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Enums\ArrayType;

class BezierFactory
{
    public static function clearCache(): void
    {
        self::$beziers = [];
    }

    /**
     * BezierEasing - use bezier curve for transition easing function
     * by Gaëtan Renaudeau 2014 - 2015 – MIT License.
     *
     * Credits: is based on Firefox's nsSMILKeySpline.cpp
     * Usage:
     * var spline = BezierEasing([ 0.25, 0.1, 0.25, 1.0 ])
     * spline.get(x) returns the easing value | x must be in [0, 1] range.
     */
    public static function getBezierEasing(
        float $a,
        float $b,
        float $c,
        float $d,
        ?string $nm = null
    ) {
        $str = $nm ?? str_replace('.', 'p', "bez_{$a}_{$b}_{$c}_{$d}");

        if (self::$beziers[$str] ?? null) {
            return self::$beziers[$str];
        }

        $bezEasing = new BezierEasing([
            $a,
            $b,
            $c,
            $d,
        ]);

        // Every distinct curve across every file ends up here; cap it for long-running processes.
        if (count(self::$beziers) >= self::MAX_CACHED) {
            self::$beziers = [];
        }
        self::$beziers[$str] = $bezEasing;

        return $bezEasing;
    }
    private static array $beziers = [];

    private const MAX_CACHED = 1000;
}

class BezierEasing
{
    private function A(float $aA1, float $aA2)
    {
        return 1.0 - 3.0 * $aA2 + 3.0 * $aA1;
    }

    private function B(float $aA1, float $aA2)
    {
        return 3.0 * $aA2 - 6.0 * $aA1;
    }

    private function binarySubdivide(
        float $aX,
        float $aAFromProps,
        float $aBFropProps,
        float $mX1,
        float $mX2
    ) {
        $aA = $aAFromProps;
        $aB = $aBFropProps;
        $currentX = null;
        $currentT = null;
        $i = 0;

        do {
            $currentT = $aA + ($aB - $aA) / 2.0;
            $currentX = $this->calcBezier(
                $currentT,
                $mX1,
                $mX2
            ) - $aX;
            if ($currentX > 0.0) {
                $aB = $currentT;
            } else {
                $aA = $currentT;
            }
        } while (
            abs($currentX) > $this::SUBDIVISION_PRECISION
            && ++$i < $this::SUBDIVISION_MAX_ITERATIONS
        );

        return $currentT;
    }

    private function C(float $aA1)
    {
        return 3.0 * $aA1;
    }

    // Private part

    /**
     * Returns x(t) given t, x1, and x2, or y(t) given t, y1, and y2.
     */
    private function calcBezier(
        float $aT,
        float $aA1,
        float $aA2
    ) {
        return (($this->A($aA1, $aA2) * $aT + $this->B($aA1, $aA2)) * $aT + $this->C($aA1)) * $aT;
    }

    /**
     * Returns dx/dt given t, x1, and x2, or dy/dt given t, y1, and y2.
     */
    private function getSlope(
        float $aT,
        float $aA1,
        float $aA2
    ) {
        return
            3.0 * $this->A($aA1, $aA2) * $aT * $aT
            + 2.0 * $this->B($aA1, $aA2) * $aT
            + $this->C($aA1);
    }

    private function newtonRaphsonIterate(
        float $aX,
        float $aGuessTFromProps,
        float $mX1,
        float $mX2
    ) {
        $aGuessT = $aGuessTFromProps;

        for ($i = 0; $i < $this::NEWTON_ITERATIONS; ++$i) {
            $currentSlope = $this->getSlope(
                $aGuessT,
                $mX1,
                $mX2
            );

            if (0.0 === $currentSlope) {
                return $aGuessT;
            }
            $currentX = $this->calcBezier(
                $aGuessT,
                $mX1,
                $mX2
            ) - $aX;

            $aGuessT -= $currentX / $currentSlope;
        }

        return $aGuessT;
    }

    public function __construct(array $points)
    {
        $this->kSampleStepSize = 1.0 / ($this->kSplineTableSize - 1.0);

        $this->_p = $points;
        $this->_mSampleValues = createTypedArray(ArrayType::Float32, $this->kSplineTableSize);
        // $this->_mSampleValues = $this->float32ArraySupported
        //   ? new Float32Array($this->kSplineTableSize)
        //   : Array.from({ length: $this->kSplineTableSize })
    }

    public function _calcSampleValues()
    {
        $mX1 = $this->_p[0];
        $mX2 = $this->_p[2];

        for ($i = 0; $i < $this->kSplineTableSize; ++$i) {
            $this->_mSampleValues[$i] = $this->calcBezier(
                $i * $this->kSampleStepSize,
                $mX1 ?? 0,
                $mX2 ?? 0
            );
        }
    }

    /**
     * GetTForX chose the fastest heuristic to determine the percentage value precisely from a given X projection.
     */
    public function _getTForX(float $aX)
    {
        $mX1 = $this->_p[0];
        $mX2 = $this->_p[2];
        $mSampleValues = $this->_mSampleValues;

        $intervalStart = 0.0;
        $currentSample = 1;
        $lastSample = $this->kSplineTableSize - 1;

        while (
            $currentSample !== $lastSample
            && (float) $mSampleValues[$currentSample] <= $aX
        ) {
            $intervalStart += $this->kSampleStepSize;

            ++$currentSample;
        }
        --$currentSample;

        // Interpolate to provide an initial guess for t
        $dist
          = ($aX - (float) $mSampleValues[$currentSample])
          / ((float) $mSampleValues[$currentSample + 1]
            - (float) $mSampleValues[$currentSample]);
        $guessForT = $intervalStart + $dist * $this->kSampleStepSize;

        $initialSlope = $this->getSlope(
            $guessForT,
            $mX1 ?? 0,
            $mX2 ?? 0
        );

        if ($initialSlope >= $this::NEWTON_MIN_SLOPE) {
            return $this->newtonRaphsonIterate(
                $aX,
                $guessForT,
                $mX1 ?? 0,
                $mX2 ?? 0
            );
        }
        if (0.0 === $initialSlope) {
            return $guessForT;
        }

        return $this->binarySubdivide(
            $aX,
            $intervalStart,
            $intervalStart + $this->kSampleStepSize,
            $mX1 ?? 0,
            $mX2 ?? 0
        );
    }

    public function _precompute()
    {
        $mX1 = $this->_p[0];
        $mY1 = $this->_p[1];
        $mX2 = $this->_p[2];
        $mY2 = $this->_p[3];

        $this->_precomputed = true;
        if ($mX1 !== $mY1 || $mX2 !== $mY2) {
            $this->_calcSampleValues();
        }
    }

    public function get(float $x)
    {
        $mX1 = $this->_p[0];
        $mY1 = $this->_p[1];
        $mX2 = $this->_p[2];
        $mY2 = $this->_p[3];

        if (!$this->_precomputed) {
            $this->_precompute();
        }
        if ($mX1 === $mY1 && $mX2 === $mY2) {
            return $x;
        } // linear
        // Because JavaScript number are imprecise, we should guarantee the extremes are right.
        if (0 === $x) {
            return 0;
        }
        if (1 === $x) {
            return 1;
        }

        return $this->calcBezier(
            $this->_getTForX($x),
            $mY1 ?? 0,
            $mY2 ?? 0
        );
    }
    private array $_mSampleValues;
    private array $_p;
    private bool $_precomputed = false;
    private float $kSampleStepSize;

    // private $float32ArraySupported = gettype(Float32Array) === 'function';
    private int $kSplineTableSize = 11;

    /**
     * These values are established by empiricism with tests (tradeoff: performance VS precision).
     */
    private const NEWTON_ITERATIONS = 4;
    private const NEWTON_MIN_SLOPE = 0.001;

    private const SUBDIVISION_MAX_ITERATIONS = 10;
    private const SUBDIVISION_PRECISION = 0.0000001;
}
