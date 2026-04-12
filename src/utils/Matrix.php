<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Enums\ArrayType;

/* !
 Transformation Matrix v2.0
 (c) Epistemex 2014-2015
 www.epistemex.com
 By Ken Fyrstenberg
 Contributions by leeoniya.
 License: MIT, header required.
 */

/**
 * 2D transformation matrix object initialized with identity matrix.
 *
 * The matrix can synchronize a canvas context by supplying the context
 * as an argument, or later apply current absolute transform to an
 * existing context.
 *
 * All values are handled as floating point values.
 */
class Matrix {
  public array $props;
  private $_identity = true;
  private $_identityCalculated = false;

  public function __construct()
  {
    $this->reset();

    $this->props = createTypedArray(ArrayType::Float32, 16);
  }

  public function applyToPoint(
    int $x,
    int $y,
    int $z
  ): array {
    return [
      'x' =>
        $x * ($this->props[0] ?? 0) +
        $y * ($this->props[4] ?? 0) +
        $z * ($this->props[8] ?? 0) +
        ($this->props[12] ?? 0),
      'y' =>
        $x * ($this->props[1] ?? 0) +
        $y * ($this->props[5] ?? 0) +
        $z * ($this->props[9] ?? 0) +
        ($this->props[13] ?? 0),
      'z' =>
        $x * ($this->props[2] ?? 0) +
        $y * ($this->props[6] ?? 0) +
        $z * ($this->props[10] ?? 0) +
        ($this->props[14] ?? 0),
    ];
  }

  public function applyToPointArray(
    int $x, int $y, int $z
  ): array {
    if ($this->isIdentity()) {
      return [$x,
        $y,
        $z];
    }

    return [
      $x * ($this->props[0] ?? 0) +
      $y * ($this->props[4] ?? 0) +
      $z * ($this->props[8] ?? 0) +
      ($this->props[12] ?? 0),
      $x * ($this->props[1] ?? 0) +
      $y * ($this->props[5] ?? 0) +
      $z * ($this->props[9] ?? 0) +
      ($this->props[13] ?? 0),
      $x * ($this->props[2] ?? 0) +
      $y * ($this->props[6] ?? 0) +
      $z * ($this->props[10] ?? 0) +
      ($this->props[14] ?? 0),
    ];
  }

  public function applyToPointStringified(int $x, int $y): string {
    if ($this->isIdentity()) {
      return "{$x},{$y}";
    }
    $_p = $this->props;

    return round(($x * ($_p[0] ?? 0) + $y * ($_p[4] ?? 0) + ($_p[12] ?? 0)) * 100) / 100 . ',' . round(($x * ($_p[1] ?? 0) + $y * ($_p[5] ?? 0) + ($_p[13] ?? 0)) * 100) / 100;
  }

  public function applyToTriplePoints(
    array $pt1,
    array $pt2,
    array $pt3
  ): array {
    $arr = createTypedArray(ArrayType::Float32, 6);

    if ($this->isIdentity()) {
      $arr[] = [
        $pt1[0] ?? 0,
        $pt1[1] ?? 0,
        $pt2[0] ?? 0,
        $pt2[1] ?? 0,
        $pt3[0] ?? 0,
        $pt3[1] ?? 0
      ];
    } else {
      $p0 = $this->props[0] ?? 0;
      $p1 = $this->props[1] ?? 0;
      $p4 = $this->props[4] ?? 0;
      $p5 = $this->props[5] ?? 0;
      $p12 = $this->props[12] ?? 0;
      $p13 = $this->props[13] ?? 0;

      $arr[] = [
        ($pt1[0] ?? 0) * $p0 + ($pt1[1] ?? 0) * $p4 + $p12,
        ($pt1[0] ?? 0) * $p1 + ($pt1[1] ?? 0) * $p5 + $p13,
        ($pt2[0] ?? 0) * $p0 + ($pt2[1] ?? 0) * $p4 + $p12,
        ($pt2[0] ?? 0) * $p1 + ($pt2[1] ?? 0) * $p5 + $p13,
        ($pt3[0] ?? 0) * $p0 + ($pt3[1] ?? 0) * $p4 + $p12,
        ($pt3[0] ?? 0) * $p1 + ($pt3[1] ?? 0) * $p5 + $p13,
      ];
    }

    return $arr;
  }

  public function applyToX(
    int $x, int $y, int $z
  ): int {
    return (
      $x * ($this->props[0] ?? 0) + $y * ($this->props[4] ?? 0) + $z * ($this->props[8] ?? 0) + ($this->props[12] ?? 0)
    );
  }

  public function applyToY(
    int $x, int $y, int $z
  ): int {
    return (
      $x * ($this->props[1] ?? 0) + $y * ($this->props[5] ?? 0) + $z * ($this->props[9] ?? 0) + ($this->props[13] ?? 0)
    );
  }

  public function applyToZ(
    int $x, int $y, int $z
  ): int {
    return (
      $x * $this->props[2] +
      $y * $this->props[6] +
      $z * $this->props[10] +
      $this->props[14]
    );
  }

  public function clone(Matrix $matr): Matrix {
    $matr->props[] = $this->props;

    return $matr;
  }

  public function cloneFromProps(array $props): Matrix {
    $this->props[] = $props;

    return $this;
  }

  public function equals(?Matrix $matr): bool {
    $length = count($this->props);

    for ($i = 0; $i < $length; $i++) {
      $val = $this->props[$i];

      if ($val !== $matr?->props[$i]) {
        return false;
      }
    }

    return true;
  }

  public function getInverseMatrix(): Matrix {
    $determinant =
      ($this->props[0] ?? 0) * ($this->props[5] ?? 0) - ($this->props[1] ?? 0) * ($this->props[4] ?? 0);
    $a = $this->props[5] / $determinant;
    $b = -$this->props[1] / $determinant;
    $c = -$this->props[4] / $determinant;
    $d = $this->props[0] / $determinant;
    $e =
      ($this->props[4] * $this->props[13] - $this->props[5] * $this->props[12]) /
      $determinant;
    $f =
      -($this->props[0] * $this->props[13] - $this->props[1] * $this->props[12]) /
      $determinant;

    $inverseMatrix = new Matrix();

    $inverseMatrix->setTransform(
      $a, $b, 0, 0, $c, $d, 0, 0, 0, 0, 1, 0, $e, $f, 0, 1
    );

    return $inverseMatrix;
  }

  public function inversePoint(array $pt): array {
    $inverseMatrix = $this->getInverseMatrix();

    return $inverseMatrix->applyToPoint(
      $pt[0] ?? 0, $pt[1] ?? 0, $pt[2] ?? 0
    );
  }

  public function inversePoints(array $pts): array {
    return array_map(fn($pt) => $this->inversePoint($pt), $pts);
  }

  public function isIdentity(): bool {
    if (!$this->_identityCalculated) {
      $this->_identity = !(
        $this->props[0] !== 1 ||
        $this->props[1] !== 0 ||
        $this->props[2] !== 0 ||
        $this->props[3] !== 0 ||
        $this->props[4] !== 0 ||
        $this->props[5] !== 1 ||
        $this->props[6] !== 0 ||
        $this->props[7] !== 0 ||
        $this->props[8] !== 0 ||
        $this->props[9] !== 0 ||
        $this->props[10] !== 1 ||
        $this->props[11] !== 0 ||
        $this->props[12] !== 0 ||
        $this->props[13] !== 0 ||
        $this->props[14] !== 0 ||
        $this->props[15] !== 1
      );
      $this->_identityCalculated = true;
    }

    return $this->_identity;
  }

  public function multiply(Matrix $matrix): Matrix {
    return $this->transform(...$matrix->props);
  }

  public function reset(): Matrix {
    $this->props[] = [1,
      0,
      0,
      0,
      0,
      1,
      0,
      0,
      0,
      0,
      1,
      0,
      0,
      0,
      0,
      1];

    return $this;
  }

  public function rotate(?int $angle): Matrix {
    if (!$angle) {
      return $this;
    }
    $mCos = cos($angle);
    $mSin = sin($angle);

    return $this->_t(
      $mCos, -$mSin, 0, 0, $mSin, $mCos, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1
    );
  }

  public function rotateX(?int $angle): Matrix {
    if (!$angle) {
      return $this;
    }
    $mCos = cos($angle);
    $mSin = sin($angle);

    return $this->_t(
      1, 0, 0, 0, 0, $mCos, -$mSin, 0, 0, $mSin, $mCos, 0, 0, 0, 0, 1
    );
  }

  public function rotateY(?int $angle): Matrix {
    if (!$angle) {
      return $this;
    }
    $mCos = cos($angle);
    $mSin = sin($angle);

    return $this->_t(
      $mCos, 0, $mSin, 0, 0, 1, 0, 0, -$mSin, 0, $mCos, 0, 0, 0, 0, 1
    );
  }

  public function rotateZ(?int $angle): Matrix {
    return $this->rotate($angle);
  }

  public function scale(
    int $sx, int $sy, $sz = 1
  ): Matrix {
    if ($sx === 1 && $sy === 1 && $sz === 1) {
      return $this;
    }

    return $this->_t(
      $sx, 0, 0, 0, 0, $sy, 0, 0, 0, 0, $sz, 0, 0, 0, 0, 1
    );
  }

  public function setTransform(
    int $a,
    int $b,
    int $c,
    int $d,
    int $e,
    int $f,
    int $g,
    int $h,
    int $i,
    int $j,
    int $k,
    int $l,
    int $m,
    int $n,
    int $o,
    int $p
  ): Matrix {
    $this->props[] = [$a,
      $b,
      $c,
      $d,
      $e,
      $f,
      $g,
      $h,
      $i,
      $j,
      $k,
      $l,
      $m,
      $n,
      $o,
      $p];

    return $this;
  }

  public function shear(int $sx, int $sy): Matrix {
    return $this->_t(
      1, $sy, $sx, 1, 0, 0
    );
  }

  public function skew(int $ax, int $ay): Matrix {
    return $this->shear(tan($ax), tan($ay));
  }

  public function skewFromAxis(int $ax, int $angle): Matrix {
    $mCos = cos($angle);
    $mSin = sin($angle);

    return $this->_t(
      $mCos, $mSin, 0, 0, -$mSin, $mCos, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1
    )->
      _t(
        1, 0, 0, 0, tan($ax), 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1
      )->
      _t(
        $mCos, -$mSin, 0, 0, $mSin, $mCos, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1
      );
  }

  public function to2dCSS(): string {
    $_a = $this->roundMatrixProperty($this->props[0] ?? 0);
    $_b = $this->roundMatrixProperty($this->props[1] ?? 0);
    $_c = $this->roundMatrixProperty($this->props[4] ?? 0);
    $_d = $this->roundMatrixProperty($this->props[5] ?? 0);
    $_e = $this->roundMatrixProperty($this->props[12] ?? 0);
    $_f = $this->roundMatrixProperty($this->props[13] ?? 0);

    return "matrix({$_a},{$_b},{$_c},{$_d},{$_e},{$_f})";
  }

  public function toCSS(): string {
    $cssValue = 'matrix3d(';
    $v = 10000;

    for ($i = 0; $i < 16; $i++) {
      $cssValue += round(($this->props[$i] ?? 0) * $v) / $v;
      $cssValue += $i === 15 ? ')' : ',';
    }

    return $cssValue;
  }

  public function transform(
    int $a2,
    int $b2,
    int $c2,
    int $d2,
    int $e2,
    int $f2,
    int $g2,
    int $h2,
    int $i2,
    int $j2,
    int $k2,
    int $l2,
    int $m2,
    int $n2,
    int $o2,
    int $p2
  ): Matrix {
    $_p = $this->props;

    if (
      $a2 === 1 &&
      $b2 === 0 &&
      $c2 === 0 &&
      $d2 === 0 &&
      $e2 === 0 &&
      $f2 === 1 &&
      $g2 === 0 &&
      $h2 === 0 &&
      $i2 === 0 &&
      $j2 === 0 &&
      $k2 === 1 &&
      $l2 === 0
    ) {
      $_p[12] = ($_p[12] ?? 0) * $a2 + ($_p[15] ?? 0) * $m2;
      $_p[13] = ($_p[13] ?? 0) * $f2 + ($_p[15] ?? 0) * $n2;
      $_p[14] = ($_p[14] ?? 0) * $k2 + ($_p[15] ?? 0) * $o2;
      $_p[15] *= $p2;
      $this->_identityCalculated = false;

      return $this;
    }

    $a1 = $_p[0] ?? 0;
    $b1 = $_p[1] ?? 0;
    $c1 = $_p[2] ?? 0;
    $d1 = $_p[3] ?? 0;
    $e1 = $_p[4] ?? 0;
    $f1 = $_p[5] ?? 0;
    $g1 = $_p[6] ?? 0;
    $h1 = $_p[7] ?? 0;
    $i1 = $_p[8] ?? 0;
    $j1 = $_p[9] ?? 0;
    $k1 = $_p[10] ?? 0;
    $l1 = $_p[11] ?? 0;
    $m1 = $_p[12] ?? 0;
    $n1 = $_p[13] ?? 0;
    $o1 = $_p[14] ?? 0;
    $p1 = $_p[15] ?? 0;

    $_p[0] = $a1 * $a2 + $b1 * $e2 + $c1 * $i2 + $d1 * $m2;
    $_p[1] = $a1 * $b2 + $b1 * $f2 + $c1 * $j2 + $d1 * $n2;
    $_p[2] = $a1 * $c2 + $b1 * $g2 + $c1 * $k2 + $d1 * $o2;
    $_p[3] = $a1 * $d2 + $b1 * $h2 + $c1 * $l2 + $d1 * $p2;

    $_p[4] = $e1 * $a2 + $f1 * $e2 + $g1 * $i2 + $h1 * $m2;
    $_p[5] = $e1 * $b2 + $f1 * $f2 + $g1 * $j2 + $h1 * $n2;
    $_p[6] = $e1 * $c2 + $f1 * $g2 + $g1 * $k2 + $h1 * $o2;
    $_p[7] = $e1 * $d2 + $f1 * $h2 + $g1 * $l2 + $h1 * $p2;

    $_p[8] = $i1 * $a2 + $j1 * $e2 + $k1 * $i2 + $l1 * $m2;
    $_p[9] = $i1 * $b2 + $j1 * $f2 + $k1 * $j2 + $l1 * $n2;
    $_p[10] = $i1 * $c2 + $j1 * $g2 + $k1 * $k2 + $l1 * $o2;
    $_p[11] = $i1 * $d2 + $j1 * $h2 + $k1 * $l2 + $l1 * $p2;

    $_p[12] = $m1 * $a2 + $n1 * $e2 + $o1 * $i2 + $p1 * $m2;
    $_p[13] = $m1 * $b2 + $n1 * $f2 + $o1 * $j2 + $p1 * $n2;
    $_p[14] = $m1 * $c2 + $n1 * $g2 + $o1 * $k2 + $p1 * $o2;
    $_p[15] = $m1 * $d2 + $n1 * $h2 + $o1 * $l2 + $p1 * $p2;

    $this->_identityCalculated = false;

    return $this;
  }

  public function translate(
    int $tx, int $ty, ?int $tzFromProps
  ) {
    // Reassign instead of default parameter, to normalize negative and catch NaN values
    $tz = $tzFromProps || 0;

    if ($tx !== 0 || $ty !== 0 || $tz !== 0) {
      return $this->_t(
        1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, $tx, $ty, $tz, 1
      );
    }

    return $this;
  }

  private function _t(int ...$args): Matrix {
    return $this->transform(...$args);
  }

  private function roundMatrixProperty(int $val): int {
    $v = 10000;

    if ($val < 0.000001 && $val > 0 || $val > -0.000001 && $val < 0) {
      return round($val * $v) / $v;
    }

    return $val;
  }
}
