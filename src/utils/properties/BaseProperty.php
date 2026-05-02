<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Bezier;
use LottiePHP\Utils\BezierFactory;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

use function LottiePHP\Utils\createTypedArray;

abstract class BaseProperty extends DynamicPropertyContainer
{
    public ?array $_caching;
    public $_cachingAtTime;
    public bool|int $_isFirstFrame = false;
    public ?string $_name;
    public ?bool $_placeholder;
    // In this port, `$comp` is sometimes treated like a composition/element reference.
    // Keep it loose to avoid type errors during rendering.
    public mixed $comp = null;
    public array|ValueProperty $e;
    public array $effectsSequence = [];
    public BaseElement|BaseProperty|null $elem;
    public ?int $frameExpressionId;
    public ?int $frameId;
    public $g;
    public ?int $initFrame = null;
    public ?bool $k = false;
    public array $keyframesMetadata = [];
    public ?bool $kf;
    public ?bool $lock = false;
    public ?\Closure $loopIn;
    public ?\Closure $loopOut;
    public ?float $mult;
    public ?int $numKeys;
    public $offsetTime = 0;
    public ?array $propertyGroup;
    public mixed $pv; // array|float|ShapePath|null|string
    public $s;
    public ?array $sh = null;
    public ?\Closure $smooth;
    public ?int $textIndex;
    public ?int $textTotal;
    public mixed $v;
    public array|int|null $value;
    public array|int|null $vel;
    public ?bool $x;

    private static function createQuaternion(array $values): array
    {
        $heading = $values[0] * Constants::$degToRads;
        $attitude = $values[1] * Constants::$degToRads;
        $bank = $values[2] * Constants::$degToRads;
        $c1 = cos($heading / 2);
        $c2 = cos($attitude / 2);
        $c3 = cos($bank / 2);
        $s1 = sin($heading / 2);
        $s2 = sin($attitude / 2);
        $s3 = sin($bank / 2);
        $w = $c1 * $c2 * $c3 - $s1 * $s2 * $s3;
        $x = $s1 * $s2 * $c3 + $c1 * $c2 * $s3;
        $y = $s1 * $c2 * $c3 + $c1 * $s2 * $s3;
        $z = $c1 * $s2 * $c3 - $s1 * $c2 * $s3;

        return [
            $x,
            $y,
            $z,
            $w,
        ];
    }

    private static function quaternionToEuler(array $out, array $quat)
    {
        $qx = $quat[0];
        $qy = $quat[1];
        $qz = $quat[2];
        $qw = $quat[3];
        $heading = atan2(
            2 * $qy * $qw - 2 * $qx * $qz,
            1 - 2 * $qy * $qy - 2 * $qz * $qz
        );
        $attitude = asin(2 * $qx * $qy + 2 * $qz * $qw);
        $bank = atan2(
            2 * $qx * $qw - 2 * $qy * $qz,
            1 - 2 * $qx * $qx - 2 * $qz * $qz
        );

        $out[0] = $heading / Constants::$degToRads;
        $out[1] = $attitude / Constants::$degToRads;
        $out[2] = $bank / Constants::$degToRads;
    }

    /**
     * Based on Toji's https://github.com/toji/gl-matrix/.
     */
    private static function slerp(
        array $a,
        array $b,
        int $t
    ): array {
        $out = [
            0,
            0,
            0,
            0,
        ];
        $ax = $a[0];
        $ay = $a[1];
        $az = $a[2];
        $aw = $a[3];
        $bx = $b[0];
        $by = $b[1];
        $bz = $b[2];
        $bw = $b[3];
        $omega = 0;
        $cosom = 0;
        $sinom = 0;
        $scale0 = 0;
        $scale1 = 0;

        $cosom = $ax * $bx + $ay * $by + $az * $bz + $aw * $bw;
        if ($cosom < 0.0) {
            $cosom = -$cosom;
            $bx = -$bx;
            $by = -$by;
            $bz = -$bz;
            $bw = -$bw;
        }
        if (1.0 - $cosom > 0.000001) {
            $omega = acos($cosom);
            $sinom = sin($omega);
            $scale0 = sin((1.0 - $t) * $omega) / $sinom;
            $scale1 = sin($t * $omega) / $sinom;
        } else {
            $scale0 = 1.0 - $t;
            $scale1 = $t;
        }
        $out[0] = $scale0 * $ax + $scale1 * $bx;
        $out[1] = $scale0 * $ay + $scale1 * $by;
        $out[2] = $scale0 * $az + $scale1 * $bz;
        $out[3] = $scale0 * $aw + $scale1 * $bw;

        return $out;
    }

    public function addEffect(\Closure $effectFunction)
    {
        array_push($this->effectsSequence, $effectFunction);
        $this->container['addDynamicProperty']($this);
    }

    public function getSpeedAtTime(int $_frameNum)
    {
        throw new \Error(get_class($this).': Method getSpeedAtTime is not implemented');
    }

    public function getValueAtCurrentTime()
    {
        $this->_caching ??= [];

        if (null === $this->initFrame) {
            $this->initFrame = Constants::$initialDefaultFrame;
        }

        if (!$this->keyframes) {
            return;
        }

        [
            '_caching' => $_caching,
            'comp' => $comp,
            'keyframes' => $keyframes,
            'offsetTime' => $offsetTime,
        ] = (array) $this;
        $frameNum = (float) $comp?->renderedFrame - $offsetTime;
        $initTime = ($keyframes[0]['t'] ?? 0) - $offsetTime;
        $length = count($keyframes) - 1;
        $endTime = ($keyframes[$length]['t'] ?? 0) - $offsetTime;
        ['lastFrame' => $lastFrame] = $_caching;

        if (
            !(
                $frameNum === $lastFrame
        || $lastFrame !== $this->initFrame
        && ($lastFrame >= $endTime && $frameNum >= $endTime
                || $lastFrame < $initTime && $frameNum < $initTime)
            )
        ) {
            if ($lastFrame >= $frameNum) {
                $_caching['_lastKeyframeIndex'] = -1;
                $_caching['lastIndex'] = 0;
            }

            $this->pv = $this->interpolateValue($frameNum, $_caching);
        }
        $_caching['lastFrame'] = $frameNum;

        return $this->pv;
    }

    public function getValueAtTime(float $_val1, float $_val2)
    {
        throw new \Error(get_class($this).': Method getValueAtTime is not implemented');
    }

    public function getVelocityAtTime(float $_frameNum): array|int
    {
        throw new \Error(get_class($this).': Method getVelocityAtTime is not implemented');
    }

    public function initiateExpression(
        array $_elem,
        $_data,
        $_property
    ) {
        throw new \Error('Method not implemented');
    }

    public function interpolateValue(float $frameNum, array $caching = [])
    {
        ['offsetTime' => $offsetTime, 'pv' => $pv] = (array) $this;
        $newValue = [
            0,
            0,
            0,
        ];

        if (PropType::MultiDimensional === $this->propType && $pv) {
            $newValue = createTypedArray(ArrayType::Float32, count($pv));
        }

        [
            'keyframes' => $keyframes,
            'keyframesMetadata' => $keyframesMetadata,
            'propType' => $propType,
        ] = (array) $this;
        $keyframes ??= [];
        $iterationIndex = $caching['lastIndex'] ?? 0;
        $i = $iterationIndex;
        $len = count($keyframes) - 1;
        $shouldInterpolate = true;
        $keyData = $keyframes[0] ?? [];
        $nextKeyData = $keyframes[1] ?? [];

        while ($shouldInterpolate) {
            $keyData = $keyframes[$i] ?? [];
            $nextKeyData = $keyframes[$i + 1] ?? [];
            if ($i === $len - 1 && $frameNum >= $nextKeyData['t'] - $offsetTime) {
                if ($keyData['h']) {
                    $keyData = $nextKeyData;
                }
                $iterationIndex = 0;

                break;
            }
            if ($nextKeyData['t'] - $offsetTime > $frameNum) {
                $iterationIndex = $i;

                break;
            }
            if ($i < $len - 1) {
                ++$i;
            } else {
                $iterationIndex = 0;
                $shouldInterpolate = false;
            }
        }
        $keyframeMetadata = $keyframesMetadata[$i] ?? [];

        $kLen = 0;
        $perc = 0;
        $jLen = 0;
        $j = 0;
        $fnc = null;
        $nextKeyTime = $nextKeyData['t'] - $offsetTime;
        $keyTime = $keyData['t'] - $offsetTime;
        $endValue = null;

        // Fast-path exact boundary frames to avoid unnecessary easing work.
        // Many Lottie keyframes omit optional fields; returning start/end values here
        // matches the upstream behavior and prevents downstream type issues.
        if ($frameNum <= $keyTime) {
            return $keyData['s'] ?? $newValue;
        }
        if ($frameNum >= $nextKeyTime) {
            return $nextKeyData['s'] ?? $keyData['e'] ?? $keyData['s'] ?? $newValue;
        }

        if (($keyData['to'] ?? null) && ($keyData['s'] ?? null)) {
            $keyframeMetadata['bezierData'] ??= Bezier::buildBezierData(
                $keyData['s'],
                $nextKeyData['s'] ?? $keyData['e'],
                $keyData['to'],
                $keyData['ti']
            );
            ['__fnct' => $__fnct, 'bezierData' => $bezierData] = $keyframeMetadata;

            if ($frameNum >= $nextKeyTime || $frameNum < $keyTime) {
                $ind = $frameNum >= $nextKeyTime ? count($bezierData->points) - 1 : 0;

                $kLen = count($bezierData->points[$ind]['point'] ?? []);
                for ($k = 0; $k < $kLen; ++$k) {
                    $newValue[$k] = $bezierData->points[$ind]['point'][$k] ?? 0;
                }
            // $caching._lastKeyframeIndex = -1;
            } else {
                if ($__fnct) {
                    $fnc = $__fnct;
                } else {
                    $fnc = BezierFactory::getBezierEasing(
                        $keyData['o']['x'],
                        $keyData['o']['y'],
                        $keyData['i']['x'],
                        $keyData['i']['y'],
                        $keyData['n']
                    )->get(...);
                    $keyframeMetadata['__fnct'] = $fnc;
                }
                $perc = $fnc(($frameNum - $keyTime) / ($nextKeyTime - $keyTime));
                $distanceInLine = $bezierData->segmentLength * $perc;

                $segmentPerc = null;
                $addedLength
                    = $caching['lastFrame'] < $frameNum
                    && $caching['_lastKeyframeIndex'] === $i
                      ? $caching['_lastAddedLength']
                      : 0;

                $j
                  = $caching['lastFrame'] < $frameNum
                  && $caching['_lastKeyframeIndex'] === $i
                    ? $caching['_lastPoint']
                    : 0;
                $shouldInterpolate = true;
                $jLen = count($bezierData->points);
                while ($shouldInterpolate) {
                    $addedLength += $bezierData->points[$j]['partialLength'] ?? 0;
                    if (
                        0 === $distanceInLine
                        || 0 === $perc
                        || $j === count($bezierData->points) - 1
                    ) {
                        $kLen = count($bezierData->points[$j]['point'] ?? []);
                        for ($k = 0; $k < $kLen; ++$k) {
                            $newValue[$k] = $bezierData->points[$j]['point'][$k] ?? 0;
                        }

                        break;
                    }

                    if (
                        $distanceInLine >= $addedLength
                        && $distanceInLine
                          < $addedLength + ($bezierData->points[$j + 1]['partialLength'] ?? 0)
                    ) {
                        $segmentPerc
                          = ($distanceInLine - $addedLength)
                          / ($bezierData->points[$j + 1]['partialLength'] ?? 0);
                        $kLen = count($bezierData->points[$j]['point'] ?? []);
                        for ($k = 0; $k < $kLen; ++$k) {
                            $newValue[$k]
                              = ($bezierData->points[$j]['point'][$k] ?? 0)
                              + (($bezierData->points[$j + 1]['point'][$k] ?? 0)
                                - ($bezierData->points[$j]['point'][$k] ?? 0))
                                * $segmentPerc;
                        }

                        break;
                    }
                    if ($j < $jLen - 1) {
                        ++$j;
                    } else {
                        $shouldInterpolate = false;
                    }
                }
                $caching['_lastPoint'] = $j;
                $caching['_lastAddedLength']
                  = $addedLength - ($bezierData->points[$j]['partialLength'] ?? 0);
                $caching['_lastKeyframeIndex'] = $i;
            }
        } else {
            $outX = null;
            $outY = null;
            $inX = null;
            $inY = null;
            $keyValue = null;

            $len = count($keyData['s'] ?? []);
            $endValue = ($nextKeyData['s'] ?? $keyData['e']);
            if ($this->sh && 1 !== $keyData['h']) {
                if ($frameNum >= $nextKeyTime) {
                    $newValue[0] = $endValue[0];
                    $newValue[1] = $endValue[1];
                    $newValue[2] = $endValue[2];
                } elseif ($frameNum <= $keyTime && $keyData['s']) {
                    $newValue[0] = $keyData['s'][0];
                    $newValue[1] = $keyData['s'][1];
                    $newValue[2] = $keyData['s'][2];
                } else {
                    $quatStart = self::createQuaternion($keyData['s']);
                    $quatEnd = self::createQuaternion($endValue);
                    $time = ($frameNum - $keyTime) / ($nextKeyTime - $keyTime);

                    self::quaternionToEuler($newValue, self::slerp(
                        $quatStart,
                        $quatEnd,
                        $time
                    ));
                }
            } else {
                for ($i = 0; $i < $len; ++$i) {
                    if (1 !== ($keyData['h'] ?? null)) {
                        if ($frameNum >= $nextKeyTime) {
                            $perc = 1;
                        } elseif ($frameNum < $keyTime) {
                            $perc = 0;
                        } else {
                            if ('array' === gettype($keyData['o']['x'])) {
                                $keyframeMetadata['__fnct'] ??= [];
                                if ($keyframeMetadata['__fnct'][$i] ?? null) {
                                    $fnc = $keyframeMetadata['__fnct'][$i];
                                } elseif (
                                    is_array($keyData['o']['y'])
                                    && is_array($keyData['i']['y'])
                                    && is_array($keyData['i']['x'])
                                ) {
                                    $outX
                                      = $keyData['o']['x'][$i] ?? $keyData['o']['x'][0];
                                    $outY
                                      = $keyData['o']['y'][$i] ?? $keyData['o']['y'][0];
                                    $inX
                                      = $keyData['i']['x'][$i] ?? $keyData['i']['x'][0];
                                    $inY
                                      = $keyData['i']['y'][$i] ?? $keyData['i']['y'][0];
                                    $fnc = BezierFactory::getBezierEasing(
                                        $outX ?? 0,
                                        $outY ?? 0,
                                        $inX ?? 0,
                                        $inY ?? 0
                                    )->get(...);
                                    $keyframeMetadata['__fnct'][$i] = $fnc;
                                }
                            } elseif ($keyframeMetadata['__fnct'] ?? null) {
                                $fnc = $keyframeMetadata['__fnct'];
                            } else {
                                $outX = $keyData['o']['x'];
                                $outY = $keyData['o']['y'];
                                $inX = $keyData['i']['x'];
                                $inY = $keyData['i']['y'];
                                $fnc = BezierFactory::getBezierEasing(
                                    $outX,
                                    $outY,
                                    $inX,
                                    $inY
                                )->get(...);
                                $keyData['keyframeMetadata'] = $fnc;
                            }
                            $perc = ($fnc)(($frameNum - $keyTime) / ($nextKeyTime - $keyTime));
                        }
                    }

                    $endValue = $nextKeyData['s'] ?? $keyData['e'];
                    $keyValue
                      = 1 === ($keyData['h'] ?? null)
                        ? ($keyData['s'] ?? [])[$i]
                        : (float) (($keyData['s'] ?? [])[$i]) + (($endValue[$i] ?? 0) - (float) ($keyData['s'] ?? [])[$i]) * (float) $perc;

                    if (null !== $keyValue) {
                        if (PropType::MultiDimensional === $propType) {
                            $newValue[$i] = $keyValue;
                        } else {
                            $newValue = $keyValue;
                        }
                    }
                }
            }
        }
        $caching['lastIndex'] = $iterationIndex;

        return $newValue;
    }

    public function processEffectsSequence(?bool $flag = null)
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        if (!$this->elem) {
            throw new \Error(get_class($this).': elem (ElementInterface) is not implemented');
        }
        if (($this->elem->globalData['frameId'] ?? 0) === $this->frameId) {
            return 0;
        }
        if (0 === count($this->effectsSequence)) {
            $this->_mdf = false;

            return 0;
        }
        if ($this->lock && $this->pv) {
            $this->setVValue($this->pv);

            return 0;
        }
        $this->lock = true;
        $this->_mdf = (bool) $this->_isFirstFrame;
        $len = count($this->effectsSequence);
        $finalValue = $this->kf
          ? $this->pv
          : $this->data['k'];

        for ($i = 0; $i < $len; ++$i) {
            $finalValue = $this->effectsSequence[$i]($finalValue);
        }
        $this->setVValue($finalValue);
        $this->_isFirstFrame = false;
        $this->lock = false;
        $this->frameId = $this->elem->globalData['frameId'] ?? 0;

        return 0;
    }

    public function setVValue(mixed $val)
    {
        if (null === $val) {
            return;
        }
        $multipliedValue = 0;

        // Some Lottie exports encode unidimensional keyframes as single-item arrays (`s: [x]`).
        // Normalize that here so uni-dimensional properties update correctly.
        if (PropType::UniDimensional === $this->propType && is_array($val) && 1 === count($val) && is_numeric($val[0] ?? null)) {
            $val = $val[0];
        }

        if (is_numeric($val) && PropType::UniDimensional === $this->propType) {
            $multipliedValue = $val * (float) $this->mult;
            if (abs($this->v - $multipliedValue) > 0.00001) {
                $this->v = $multipliedValue;
                $this->_mdf = true;
            }

            return;
        }
        $i = 0;
        $length = is_array($this->v) ? count($this->v) : 0;

        while ($i < $length) {
            $multipliedValue = ($val[$i] ?? 0) * (float) $this->mult;
            if (abs(($this->v[$i] ?? 0) - $multipliedValue) > 0.00001) {
                $this->v[$i] = $multipliedValue;
                $this->_mdf = true;
            }
            ++$i;
        }
    }

    public function speedAtTime(int $_frameNum)
    {
        throw new \Error('Method is not implemented');
    }

    public function valueAtTime(int $_a, $_b = 0)
    {
        throw new \Error(get_class($this).': Method valueAtTime is not implemented');
    }

    public function velocityAtTime(int $_frameNum)
    {
        throw new \Error('Method is not implemented');
    }
}
