<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\BezierFactory;
use LottiePHP\Utils\Enums\ShapeType;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Shapes\ShapeCollection;
use LottiePHP\Utils\Shapes\ShapePath;

abstract class ShapeBaseProperty extends DynamicPropertyContainer
{
    public ?array $_caching;
    public ?array $_cachingAtTime;
    public ?BaseElement $comp;
    public ?array $data = null;
    public array $effectsSequence = [];
    public BaseElement $elem;
    public ?int $frameId = null;
    public ?bool $k;
    public array $keyframesMetadata = [];
    public ?bool $kf;
    public ?ShapeCollection $localShapeCollection;
    public ?bool $lock = false;
    public float $offsetTime = 0;
    public ?BaseProperty $p;
    public array|ShapeCollection|null $paths;
    public ?ShapePath $pv;
    public ?ShapeType $ty;
    public ?ShapePath $v;

    public function getValueAtTime(float $_frameNumFromProps, ?float $_num): ?ShapePath
    {
        throw new \Error(get_class($this).': Method getShapeValueAtTime is not implemented');
    }

    public function initiateExpression(
        BaseElement $_elem,
        array $_data,
        BaseProperty $_property
    ) {
        throw new \Error(get_class($this).': Method initiateExpression is not implemented');
    }

    public function interpolateShape(
        int $frameNum,
        ShapePath $previousValue,
        array $caching = []
    ) {
        $iterationIndex = $caching['lastIndex'] ?? 0;
        $keyPropS = null;
        $keyPropE = null;
        $isHold = null;
        $perc = 0;
        $vertexValue = 0;
        $kf = $this->keyframes ?? [];

        if ($frameNum < ($kf[0]['t'] ?? 0) - $this->offsetTime) {
            $keyPropS = $kf[0]['s'][0] ?? 0;
            $isHold = true;
            $iterationIndex = 0;
        } elseif ($frameNum >= ($kf[count($kf) - 1]['t'] ?? 0) - $this->offsetTime) {
            $keyPropS = $kf[count($kf) - 1]['s']
              ? $kf[count($kf) - 1]['s'][0]
              : $kf[count($kf) - 2]['e'][0];
            /* if(kf[kf.length - 1].s){
                      $keyPropS = kf[kf.length - 1].s[0];
                  }else{
                      $keyPropS = kf[kf.length - 2].e[0];
                  } */
            $isHold = true;
        } else {
            $i = $iterationIndex;
            $len = count($kf) - 1;
            $shouldInterpolate = true;
            $keyData = null;
            $nextKeyData = null;

            while ($shouldInterpolate) {
                $keyData = $kf[$i];
                $nextKeyData = $kf[$i + 1];
                if (($nextKeyData['t'] ?? 0) - $this->offsetTime > $frameNum) {
                    break;
                }
                if ($i < $len - 1) {
                    ++$i;
                } else {
                    $shouldInterpolate = false;
                }
            }
            if (!$keyData || !$nextKeyData) {
                throw new \Error(get_class($this).': Could not set keyframe data');
            }
            $keyframeMetadata = $this->keyframesMetadata[$i] ?? [];

            $isHold = 1 === ($keyData['h'] ?? 0);
            $iterationIndex = $i;
            if (!$isHold) {
                if ($frameNum >= $nextKeyData['t'] - $this->offsetTime) {
                    $perc = 1;
                } elseif ($frameNum < $keyData['t'] - $this->offsetTime) {
                    $perc = 0;
                } else {
                    $fnc = null;

                    if (isset($keyframeMetadata['__fnct']) && $keyframeMetadata['__fnct']) {
                        $fnc = $keyframeMetadata['__fnct'];
                    } elseif (
                        is_numeric($keyData['o']['x'])
                        && is_numeric($keyData['o']['y'])
                        && is_numeric($keyData['i']['x'])
                        && is_numeric($keyData['i']['y'])
                    ) {
                        $fnc = BezierFactory::getBezierEasing(
                            $keyData['o']['x'],
                            $keyData['o']['y'],
                            $keyData['i']['x'],
                            $keyData['i']['y']
                        )->get(...);
                        $keyframeMetadata['__fnct'] = $fnc;
                    }
                    $perc
                      = ($fnc(($frameNum - ($keyData['t'] - $this->offsetTime))
                        / ($nextKeyData['t']
                          - $this->offsetTime
                          - ($keyData['t'] - $this->offsetTime))) ?? 0);
                }
                $keyPropE = $nextKeyData['s'] ? $nextKeyData['s'][0] : $keyData['e'][0];
            }
            $keyPropS = $keyData['s'][0] ?? null;
        }
        // Lottie keyframed shapes are represented as arrays in PHP, shaped like:
        // ['i' => [[x,y]...], 'o' => [[x,y]...], 'v' => [[x,y]...], 'c' => bool]
        if (!$keyPropS || !is_array($keyPropS)) {
            return;
        }
        if (!$isHold && (!is_array($keyPropE) || !$keyPropE)) {
            // If we can't interpolate, fall back to hold.
            $isHold = true;
        }

        $jLen = $previousValue->_length;
        $kLen = count($keyPropS['i'][0] ?? []);

        $caching['lastIndex'] = $iterationIndex;

        for ($j = 0; $j < $jLen; ++$j) {
            for ($k = 0; $k < $kLen; ++$k) {
                $vertexValue = ($isHold
                  ? ($keyPropS['i'][$j][$k] ?? 0)
                  : ($keyPropS['i'][$j][$k] ?? 0) + (($keyPropE['i'][$j][$k] ?? 0) - ($keyPropS['i'][$j][$k] ?? 0)) * $perc) ?? 0;
                $previousValue->i[$j][$k] = $vertexValue;
                $vertexValue = ($isHold
                  ? ($keyPropS['o'][$j][$k] ?? 0)
                  : ($keyPropS['o'][$j][$k] ?? 0) + (($keyPropE['o'][$j][$k] ?? 0) - ($keyPropS['o'][$j][$k] ?? 0)) * $perc) ?? 0;
                $previousValue->o[$j][$k] = $vertexValue;
                $vertexValue = ($isHold
                  ? ($keyPropS['v'][$j][$k] ?? 0)
                  : ($keyPropS['v'][$j][$k] ?? 0) + (($keyPropE['v'][$j][$k] ?? 0) - ($keyPropS['v'][$j][$k] ?? 0)) * $perc) ?? 0;
                $previousValue->v[$j][$k] = $vertexValue;
            }
        }
    }

    public function interpolateShapeCurrentTime()
    {
        if (!$this->pv) {
            throw new \Error(get_class($this).': Cannot parse ShapePath v value');
        }

        if (!$this->keyframes) {
            return;
        }

        $this->_caching ??= [];

        $frameNum = (int) $this->comp?->renderedFrame - $this->offsetTime;
        $initTime = ($this->keyframes[0]['t'] ?? 0) - $this->offsetTime;
        $endTime = ($this->keyframes[count($this->keyframes) - 1]['t'] ?? 0) - $this->offsetTime;
        ['lastFrame' => $lastFrame, 'lastIndex' => $lastIndex] = $this->_caching;

        if (
            !(
                $lastFrame !== Constants::$initialDefaultFrame
        && ($lastFrame < $initTime && $frameNum < $initTime
                || $lastFrame > $endTime && $frameNum > $endTime)
            )
        ) {
            $this->_caching['lastIndex']
              = $lastFrame < $frameNum ? $lastIndex : 0;
            $this->interpolateShape(
                $frameNum,
                $this->pv,
                $this->_caching
            );
        }
        $this->_caching['lastFrame'] = $frameNum;

        return $this->pv;
    }

    public function processEffectsSequence($_val = null)
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (Shape) is not implemented');
        }

        if (($this->elem?->globalData['frameId'] ?? null) === $this->frameId) {
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
        $this->_mdf = false;
        $finalValue = null;

        if ($this->kf) {
            $finalValue = $this->pv;
        } elseif ($this->data['ks']) {
            $finalValue = $this->data['ks']['k'];
        } else {
            $finalValue = $this->data['pt']['k'] ?? null;
        }
        $i = 0;
        $len = count($this->effectsSequence);

        for ($i = 0; $i < $len; ++$i) {
            $finalValue = $this->effectsSequence[$i]($finalValue);
        }
        $this->setVValue($finalValue);
        $this->lock = false;
        $this->frameId = $this->elem?->globalData['frameId'] ?? 0;

        return 0;
    }

    public function reset()
    {
        $this->paths = $this->localShapeCollection;
    }

    public function setVValue(?ShapePath $newPath)
    {
        if (!$this->v || !$newPath) {
            throw new \Error(get_class($this).': ShapePath is not set');
        }
        if (!$this->localShapeCollection) {
            throw new \Error(get_class($this).': localShapeCollection is not set');
        }
        if (!$this->shapesEqual($this->v, $newPath)) {
            $this->v = clone $newPath;
            $this->localShapeCollection->releaseShapes();
            $this->localShapeCollection->addShape($this->v);
            $this->_mdf = true;
            $this->paths = $this->localShapeCollection;
        }
    }

    public function shapesEqual(ShapePath $shape1, ShapePath $shape2)
    {
        if ($shape1->_length !== $shape2->_length ?? $shape1->c !== $shape2->c) {
            return false;
        }
        $len = $shape1->_length ?? 0;

        for ($i = 0; $i < $len; ++$i) {
            if (
                $shape1->v[$i][0] !== $shape2->v[$i][0]
                || $shape1->v[$i][1] !== $shape2->v[$i][1]
                || $shape1->o[$i][0] !== $shape2->o[$i][0]
                || $shape1->o[$i][1] !== $shape2->o[$i][1]
                || $shape1->i[$i][0] !== $shape2->i[$i][0]
                || $shape1->i[$i][1] !== $shape2->i[$i][1]
            ) {
                return false;
            }
        }

        return true;
    }
}
