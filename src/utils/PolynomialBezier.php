<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Shapes\ShapePath;

class PolynomialBezier
{
    public array $a;
    public array $b;
    public array $c;
    public array $d;
    public array $points;
    public function __construct(
        array $p0,
        array $p1FromProps,
        array $p2FromProps,
        array $p3,
        ?bool $linearize = null
    ) {
        $p1 = $p1FromProps;
        $p2 = $p2FromProps;

        if ($linearize && pointEqual($p0, $p1)) {
            $p1 = $this->lerpPoint(
                $p0,
                $p3,
                1 / 3
            );
        }
        if ($linearize && pointEqual($p2, $p3)) {
            $p2 = $this->lerpPoint(
                $p0,
                $p3,
                2 / 3
            );
        }
        $coeffx = $this->polynomialCoefficients(
            $p0[0],
            $p1[0],
            $p2[0],
            $p3[0]
        );
        $coeffy = $this->polynomialCoefficients(
            $p0[1],
            $p1[1],
            $p2[1],
            $p3[1]
        );

        $this->a = [$coeffx[0], $coeffy[0]];
        $this->b = [$coeffx[1], $coeffy[1]];
        $this->c = [$coeffx[2], $coeffy[2]];
        $this->d = [$coeffx[3], $coeffy[3]];
        $this->points = [$p0,
          $p1,
          $p2,
          $p3];
    }

    public function boundingBox()
    {
        $bounds = $this->bounds();

        return [
          'bottom' => $bounds['y']['max'],
          'cx' => ($bounds['x']['max'] + $bounds['x']['min']) / 2,
          'cy' => ($bounds['y']['max'] + $bounds['y']['min']) / 2,
          'height' => $bounds['y']['max'] - $bounds['y']['min'],
          'left' => $bounds['x']['min'],
          'right' => $bounds['x']['max'],
          'top' => $bounds['y']['min'],
          'width' => $bounds['x']['max'] - $bounds['x']['min'],
        ];
    }

    public function bounds()
    {
        return [
          'x' => $this->_extrema($this, 0),
          'y' => $this->_extrema($this, 1),
        ];
    }

    public function derivative(int $t)
    {
        return [
          (3 * $t * $this->a[0] + 2 * $this->b[0]) * $t + $this->c[0], (3 * $t * $this->a[1] + 2 * $this->b[1]) * $t + $this->c[1],
        ];
    }

    public function inflectionPoints()
    {
        $denom = $this->a[1] * $this->b[0] - $this->a[0] * $this->b[1];

        if (floatZero($denom)) {
            return [];
        }
        $tcusp =
          -0.5 * ($this->a[1] * $this->c[0] - $this->a[0] * $this->c[1]) / $denom;
        $square =
            $tcusp * $tcusp -
            1 / 3 * ($this->b[1] * $this->c[0] - $this->b[0] * $this->c[1]) / $denom;

        if ($square < 0) {
            return [];
        }
        $root = sqrt($square);

        if (floatZero($root)) {
            if ($root > 0 && $root < 1) {
                return [$tcusp];
            }

            return [];
        }

        return array_filter([$tcusp - $root, $tcusp + $root], fn ($r) => $r > 0 && $r < 1);

    }

    public function intersections(
        PolynomialBezier $other,
        ?int $toleranceFromProps = null,
        ?int $maxRecursionFromProps = null
    ) {
        $tolerance = $toleranceFromProps;
        $maxRecursion = $maxRecursionFromProps;

        $tolerance = $tolerance ?? 2;
        $maxRecursion = $maxRecursion ?? 7;
        $intersections = [];

        $this->intersectsImpl(
            $this->intersectData(
                $this,
                0,
                1
            ),
            $this->intersectData(
                $other,
                0,
                1
            ),
            0,
            $tolerance,
            $intersections,
            $maxRecursion
        );

        return $intersections;
    }

    public function normalAngle(int $t)
    {
        $p = $this->derivative($t);

        return atan2($p[0] ?? 0, $p[1] ?? 0);
    }

    public function point(int $t)
    {
        return [
          (($this->a[0] * $t + $this->b[0]) * $t + $this->c[0]) * $t + $this->d[0], (($this->a[1] * $t + $this->b[1]) * $t + $this->c[1]) * $t + $this->d[1],
        ];
    }

    public function split(int $t)
    {
        if ($t <= 0) {
            return [$this->singlePoint($this->points[0]), $this];
        }
        if ($t >= 1) {
            return [$this, $this->singlePoint($this->points[count($this->points) - 1])];
        }
        $p10 = $this->lerpPoint(
            $this->points[0],
            $this->points[1],
            $t
        );
        $p11 = $this->lerpPoint(
            $this->points[1],
            $this->points[2],
            $t
        );
        $p12 = $this->lerpPoint(
            $this->points[2],
            $this->points[3],
            $t
        );
        $p20 = $this->lerpPoint(
            $p10,
            $p11,
            $t
        );
        $p21 = $this->lerpPoint(
            $p11,
            $p12,
            $t
        );
        $p3 = $this->lerpPoint(
            $p20,
            $p21,
            $t
        );

        return [
          new PolynomialBezier(
              $this->points[0],
              $p10,
              $p20,
              $p3,
              true
          ), new PolynomialBezier(
              $p3,
              $p21,
              $p12,
              $this->points[3],
              true
          ),
        ];
    }

    public function tangentAngle(int $t)
    {
        $p = $this->derivative($t);

        return atan2($p[1] ?? 0, $p[0] ?? 0);
    }

    private function _extrema(PolynomialBezier $bez, int $comp)
    {
        $min = $bez->points[0][$comp] ?? 0;
        $max = $bez->points[count($bez->points) - 1][$comp] ?? 0;

        if ($min > $max) {
            $e = $max;

            $max = $min;
            $min = $e;
        }
        // Derivative roots to find min/max
        $f = $this->quadRoots(
            3 * ($bez->a[$comp] ?? 0),
            2 * ($bez->b[$comp] ?? 0),
            $bez->c[$comp] ?? 0
        );
        $length = count($f);

        for ($i = 0; $i < $length; $i++) {
            if (($f[$i] ?? 0) <= 0 || ($f[$i] ?? 0) >= 1) {
                continue;
            }
            $val = $bez->point($f[$i] ?? 0)[$comp] ?? 0;

            if ($val < $min) {
                $min = $val;
                continue;
            }

            if ($val > $max) {
                $max = $val;
            }
        }

        return [
          'max' => $max,
          'min' => $min,
        ];
    }

    public static function shapeSegment(ShapePath $shapePath, int $index)
    {
        $nextIndex = ($index + 1) % $shapePath->length();

        return new PolynomialBezier(
            $shapePath->v[$index],
            $shapePath->o[$index],
            $shapePath->i[$nextIndex],
            $shapePath->v[$nextIndex],
            true
        );
    }

    public static function shapeSegmentInverted(ShapePath $shapePath, int $index)
    {
        $nextIndex = ($index + 1) % $shapePath->length();

        return new PolynomialBezier(
            $shapePath->v[$nextIndex],
            $shapePath->i[$nextIndex],
            $shapePath->o[$index],
            $shapePath->v[$index],
            true
        );
    }



    private function quadRoots(
        int $a,
        int $b,
        int $c
    ) {
        // no root
        if ($a === 0) {
            return [];
        }
        $$s = $b * $b - 4 * $a * $c;

        // Complex roots
        if ($s < 0) {
            return [];
        }
        $singleRoot = -$b / (2 * $a);

        // 1 root
        if ($s === 0) {
            return [$singleRoot];
        }
        $delta = sqrt($s) / (2 * $a);

        // 2 roots
        return [$singleRoot - $delta, $singleRoot + $delta];
    }

    private function polynomialCoefficients(
        int $p0,
        int $p1,
        int $p2,
        int $p3
    ) {
        return [
          -$p0 + 3 * $p1 - 3 * $p2 + $p3,
          3 * $p0 - 6 * $p1 + 3 * $p2,
          -3 * $p0 + 3 * $p1,
          $p0,
        ];
    }

    private function lerp(
        int $p0,
        int $p1,
        int $amount
    ) {
        return
          $p0 * (1 - $amount) + $p1 * $amount;
    }

    private function lerpPoint(
        array $p0,
        array $p1,
        int $amount
    ) {
        return [
          $this->lerp(
              $p0[0],
              $p1[0],
              $amount
          ), $this->lerp(
              $p0[1],
              $p1[1],
              $amount
          ),
        ];
    }

    private function boxIntersect(array $b1, array $b2)
    {
        return abs($b1['cx'] - $b2['cx']) * 2 < $b1['width'] + $b2['width'] && abs($b1['cy'] - $b2['cy']) * 2 < $b1['height'] + $b2['height'];
    }

    private function intersectData(
        PolynomialBezier $bez,
        int $t1,
        int $t2
    ) {
        $box = $bez->boundingBox();

        return [
          'bez' => $bez,
          'cx' => $box['cx'],
          'cy' => $box['cy'],
          'height' => $box['height'],
          't' => ($t1 + $t2) / 2,
          't1' => $t1,
          't2' => $t2,
          'width' => $box['width'],
        ];
    }

    private function splitData(array $data)
    {
        $split = $data['bez']->split(0.5);

        if (!$split[0] || !$split[1]) {
            throw new \Error('Could not set PolynomialBezier');
        }

        return [
          $this->intersectData(
              $split[0],
              $data['t1'],
              $data['t']
          ), $this->intersectData(
              $split[1],
              $data['t'],
              $data['t2']
          ),
        ];
    }

    private function intersectsImpl(
        array $d1,
        array $d2,
        int $depth,
        int $tolerance,
        array $intersections,
        int $maxRecursion
    ) {
        if (!$this->boxIntersect($d1, $d2)) {
            return;
        }
        if (
            $depth >= $maxRecursion ||
            $d1['width'] <= $tolerance &&
            $d1['height'] <= $tolerance &&
            $d2['width'] <= $tolerance &&
            $d2['height'] <= $tolerance
        ) {
            array_push($intersections, [$d1['t'], $d2['t']]);
            return;
        }
        $d1s = $this->splitData($d1);
        $d2s = $this->splitData($d2);

        if (!$d1s[0] || !$d1s[1] || !$d2s[0] || !$d2s[1]) {
            throw new \Error('Could not set IntersectData');
        }

        $this->intersectsImpl(
            $d1s[0],
            $d2s[0],
            $depth + 1,
            $tolerance,
            $intersections,
            $maxRecursion
        );
        $this->intersectsImpl(
            $d1s[0],
            $d2s[1],
            $depth + 1,
            $tolerance,
            $intersections,
            $maxRecursion
        );
        $this->intersectsImpl(
            $d1s[1],
            $d2s[0],
            $depth + 1,
            $tolerance,
            $intersections,
            $maxRecursion
        );
        $this->intersectsImpl(
            $d1s[1],
            $d2s[1],
            $depth + 1,
            $tolerance,
            $intersections,
            $maxRecursion
        );
    }
    private function singlePoint(array $p)
    {
        return new PolynomialBezier(
            $p,
            $p,
            $p,
            $p,
            false
        );
    }

}
