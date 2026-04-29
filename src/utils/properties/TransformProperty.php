<?php

namespace LottiePHP\Utils\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\Modifiers\ShapeModifier;

class TransformProperty extends BaseProperty
{
    public ?bool $_isDirty;
    public ?bool $_opMdf;
    public ?array $_transformCachingAtTime;
    public BaseProperty $a;
    public int $appliedTransformations = 0;
    public ?bool $autoOriented = false;
    public ?array $data;
    public BaseElement|BaseProperty|null $elem;

    /**
     * FrameId: number.
     */
    public array|BaseProperty|null $o;
    public ?int $opacity;
    public ?BaseProperty $or;
    public ?BaseProperty $p;

    /**
     * Precalculated $matrix with non animated properties.
     */
    public Matrix $pre;
    public ?ValueProperty $px;
    public KeyframedValueProperty|ValueProperty|null $py;
    public ?ValueProperty $pz;
    public KeyframedValueProperty|MultiDimensionalProperty|ValueProperty|null $r;
    public ?ValueProperty $rx;
    public ?ValueProperty $ry;
    public ?ValueProperty $rz;
    public ?ValueProperty $sa = null;
    public KeyframedValueProperty|ValueProperty|null $sk = null;

    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        BaseElement|BaseProperty|ShapeModifier|null $container = null
    ) {
        $this->pre = new Matrix();
        $this->elem = $elem;
        $this->frameId = -1;
        $this->propType = PropType::Transform;
        $this->data = $data;
        $this->v = new Matrix();
        $this->initDynamicPropertyContainer($container ?? $elem);
        $positionData = $data['p'] ?? null;
        if ($positionData && is_array($positionData) && array_key_exists('s', $positionData)) {
            $this->px = PropertyFactory::getProp(
                $elem,
                $positionData['x'],
                0,
                1,
                $this
            );
            $this->py = PropertyFactory::getProp(
                $elem,
                $positionData['y'],
                0,
                1,
                $this
            );
            if (array_key_exists('z', $positionData)) {
                $this->pz = PropertyFactory::getProp(
                    $elem,
                    $positionData['z'],
                    0,
                    1,
                    $this
                );
            }
        } else {
            $this->p = PropertyFactory::getProp(
                $elem,
                $data['p'] ?? [
                    'k' => [0,
                        0,
                        0],
                ],
                1,
                1,
                $this
            );
        }
        if (array_key_exists('rx', $data)) {
            $this->rx = PropertyFactory::getProp(
                $elem,
                $data['rx'],
                0,
                Constants::$degToRads,
                $this
            );
            $this->ry = PropertyFactory::getProp(
                $elem,
                $data['ry'],
                0,
                Constants::$degToRads,
                $this
            );
            $this->rz = PropertyFactory::getProp(
                $elem,
                $data['rz'],
                0,
                Constants::$degToRads,
                $this
            );
            if ($data['or']['k'][0]['ti'] ?? null) {
                $length = count($data['or']['k']);

                for ($i = 0; $i < $length; ++$i) {
                    $thisK = $data['or']['k'][$i];

                    if (!$thisK) {
                        continue;
                    }

                    $thisK['to'] = null;
                    $thisK['ti'] = null;
                }
            }
            $this->or = PropertyFactory::getProp(
                $elem,
                $data['or'],
                1,
                Constants::$degToRads,
                $this
            );
            $this->or['sh'] = true;
        } else {
            $this->r = PropertyFactory::getProp(
                $elem,
                $data['r'] ?? ['k' => 0],
                0,
                Constants::$degToRads,
                $this
            );
        }
        if (array_key_exists('sk', $data)) {
            $this->sk = PropertyFactory::getProp(
                $elem,
                $data['sk'],
                0,
                Constants::$degToRads,
                $this
            );
            $this->sa = PropertyFactory::getProp(
                $elem,
                $data['sa'],
                0,
                Constants::$degToRads,
                $this
            );
        }
        $this->a = PropertyFactory::getProp(
            $elem,
            $data['a'] ?? [
                'k' => [0,
                    0,
                    0],
            ],
            1,
            1,
            $this
        );
        $this->s = PropertyFactory::getProp(
            $elem,
            $data['s'] ?? [
                'k' => [100,
                    100,
                    100],
            ],
            1,
            0.01,
            $this
        );
        // Opacity is not part of the transform properties, that's why it won't use $this->dynamicProperties. That way transforms won't get updated if opacity changes.
        if ($data['o'] ?? null) {
            $this->o = PropertyFactory::getProp(
                $elem,
                $data['o'],
                0,
                0.01,
                $elem
            );
        } else {
            $this->o = [
                '_mdf' => false,
                'v' => 1,
            ];
        }
        $this->_isDirty = true;

        if (0 === count($this->dynamicProperties)) {
            $this->getValue(true);
        }
    }

    public function addDynamicProperty(DynamicPropertyContainer $prop)
    {
        parent::addDynamicProperty($prop);
        // The element needs to tick the TransformProperty itself so it can
        // rebuild the composed matrix (`$this->v`) from its child properties.
        $this->elem->addDynamicProperty($this);
        $this->_isDirty = true;
    }

    public function applyToMatrix(Matrix $mat)
    {
        // This check is useful for develpment
        if (!$this->data) {
            throw new \Error(get_class($this).': (Shape) is not implemented');
        }

        $this->iterateDynamicProperties();
        $this->_mdf = (bool) $this->_mdf;
        if ($this->a) {
            $mat->translate(
                -$this->a->v[0],
                -$this->a->v[1],
                $this->a->v[2] ?? 0
            );
        }
        if ($this->s) {
            $mat->scale(
                $this->s->v[0],
                $this->s->v[1],
                $this->s->v[2] ?? 1.0
            );
        }
        if ($this->sk) {
            $mat->skewFromAxis(-$this->sk->v, $this->sa->v ?? 0);
        }
        if ($this->r) {
            $mat->rotate(-$this->r->v);
        } else {
            $mat
                ->rotateZ(-$this->rz->v ?? 0.0)
                ->rotateY($this->ry->v ?? 0.0)
                ->rotateX($this->rx->v ?? 0.0)
                ->rotateZ(-($this->or->v[2] ?? 0.0))
                ->rotateY($this->or->v[1] ?? 0.0)
                ->rotateX($this->or->v[0] ?? 0.0)
            ;
        }
        $positionData = $this->data['p'] ?? null;
        if ($positionData && is_array($positionData) && array_key_exists('s', $positionData)) {
            if (array_key_exists('z', $positionData)) {
                $mat->translate(
                    $this->px->v ?? 0.0,
                    $this->py->v ?? 0.0,
                    -($this->pz->v ?? 0.0)
                );
            } else {
                $mat->translate(
                    $this->px->v ?? 0.0,
                    $this->py->v ?? 0.0,
                    0
                );
            }
        } else {
            $mat->translate(
                $this->p->v[0] ?? 0.0,
                $this->p->v[1] ?? 0.0,
                -($this->p->v[2] ?? 0.0)
            );
        }
    }

    public function autoOrient()
    {
        throw new \Error(get_class($this).': Method autoOrient not implemented');
    }

    public function getValue($forceRender = false)
    {
        if ($this->elem->globalData['frameId'] === $this->frameId) {
            return 0;
        }
        if ($this->_isDirty) {
            $this->precalculateMatrix();
            $this->_isDirty = false;
        }

        $this->iterateDynamicProperties();

        if ($this->_mdf || $forceRender) {
            // $frameRate

            $this->v->cloneFromProps($this->pre->props);
            if ($this->appliedTransformations < 1 && $this->a) {
                $this->v->translate(
                    -$this->a->v[0],
                    -$this->a->v[1],
                    $this->a->v[2] ?? 0.0
                );
            }
            if ($this->appliedTransformations < 2 && $this->s) {
                $this->v->scale(
                    $this->s->v[0],
                    $this->s->v[1],
                    $this->s->v[2] ?? 1.0
                );
            }
            if ($this->sk && $this->appliedTransformations < 3) {
                $this->v->skewFromAxis(-$this->sk->v, $this->sa->v ?? 0.0);
            }
            if ($this->r && $this->appliedTransformations < 4) {
                $this->v->rotate(-$this->r->v);
            } elseif (!$this->r && $this->appliedTransformations < 4) {
                $this->v
                    ->rotateZ(-($this->rz->v ?? 0.0))
                    ->rotateY($this->ry->v ?? 0.0)
                    ->rotateX($this->rx->v ?? 0.0)
                    ->rotateZ(-($this->or->v[2] ?? 0.0))
                    ->rotateY($this->or->v[1] ?? 0.0)
                    ->rotateX($this->or->v[0] ?? 0.0)
                ;
            }
            if ($this->autoOriented) {
                $v1 = null;
                $v2 = null;

                ['frameRate' => $frameRate] = $this->elem->globalData ?? ['frameRate' => 60];

                if ($this->p->keyframes ?? null) {
                    if (
                        ($this->p->_caching['lastFrame'] ?? 0) + $this->p->offsetTime
                        <= ($this->p->keyframes[0]['t'] ?? 0)
                    ) {
                        $v1 = $this->p->getValueAtTime(
                            (($this->p->keyframes[0]['t'] ?? 0) + 0.01) / $frameRate,
                            0
                        );
                        $v2 = $this->p->getValueAtTime(
                            ($this->p->keyframes[0]['t'] ?? 0.0) / $frameRate,
                            0
                        );
                    } elseif (
                        ($this->p->_caching['lastFrame'] ?? 0) + $this->p->offsetTime
                        >= ($this->p->keyframes[count($this->p->keyframes) - 1]['t'] ?? 0)
                    ) {
                        $v1 = $this->p->getValueAtTime(
                            ($this->p->keyframes[count($this->p->keyframes) - 1]['t'] ?? 0) / $frameRate,
                            0
                        );
                        $v2 = $this->p->getValueAtTime(
                            (($this->p->keyframes[count($this->p->keyframes) - 1]['t'] ?? 0) - 0.05)
              / $frameRate,
                            0
                        );
                    } else {
                        $v1 = $this->p->pv;
                        $v2 = $this->p->getValueAtTime(
                            (($this->p->_caching['lastFrame'] ?? 0.0)
              + $this->p->offsetTime
              - 0.01)
              / $frameRate,
                            $this->p->offsetTime
                        );
                    }
                } elseif ($this->px?->keyframes && $this->py?->keyframes) {
                    $v1 = [0, 0];
                    $v2 = [0, 0];

                    $pxKeyframes = $this->px?->keyframes ?? [];
                    $pyKeyframes = $this->py?->keyframes ?? [];

                    if (
                        ($this->px->_caching['lastFrame'] ?? 0) + $this->px->offsetTime
                        <= ($pxKeyframes[0]['t'] ?? 0)
                    ) {
                        $v1[0] = $this->px->getValueAtTime(
                            (($pxKeyframes[0]['t'] ?? 0) + 0.01) / $frameRate,
                            0
                        );
                        $v1[1] = $this->py->getValueAtTime(
                            (($pyKeyframes[0]['t'] ?? 0) + 0.01) / $frameRate,
                            0
                        );
                        $v2[0] = $this->px->getValueAtTime(
                            ($pxKeyframes[0]['t'] ?? 0) / $frameRate,
                            0
                        );
                        $v2[1] = $this->py->getValueAtTime(
                            $pyKeyframes[0]['t'] / $frameRate,
                            0
                        );
                    } elseif (
                        (float) $this->px->_caching['lastFrame'] + $this->px->offsetTime
                        >= ($pxKeyframes[count($pxKeyframes) - 1]['t'] ?? 0)
                    ) {
                        $v1[0] = $this->px->getValueAtTime(
                            ($pxKeyframes[count($pxKeyframes) - 1]['t'] ?? 0)
              / $frameRate,
                            0
                        );
                        $v1[1] = $this->py->getValueAtTime(
                            ($pyKeyframes[count($pyKeyframes) - 1]['t'] ?? 0)
              / $frameRate,
                            0
                        );
                        $v2[0] = $this->px->getValueAtTime(
                            (($pxKeyframes[count($pxKeyframes) - 1]['t'] ?? 0)
              - 0.01)
              / $frameRate,
                            0
                        );
                        $v2[1] = $this->py->getValueAtTime(
                            (($pyKeyframes[count($pyKeyframes) - 1]['t'] ?? 0)
              - 0.01)
              / $frameRate,
                            0
                        );
                    } else {
                        $v1 = [$this->px->pv, $this->py->pv];
                        $v2[0] = $this->px->getValueAtTime(
                            (($this->px->_caching['lastFrame'] ?? 0.0) + $this->px->offsetTime - 0.01)
              / $frameRate,
                            $this->px->offsetTime
                        );
                        $v2[1] = $this->py->getValueAtTime(
                            (($this->py->_caching['lastFrame'] ?? 0.0) + $this->py->offsetTime - 0.01)
              / $frameRate,
                            $this->py->offsetTime
                        );
                    }
                } else {
                    $v2 = $this->defaultVector;
                    $v1 = $v2;
                }
                $this->v->rotate(-atan2($v1[1] - $v2[1], $v1[0] - $v2[0]));
            }
            $positionData = $this->data['p'] ?? null;
            if ($positionData && is_array($positionData) && array_key_exists('s', $positionData)) {
                if (array_key_exists('z', $positionData)) {
                    $this->v->translate(
                        $this->px->v ?? 0.0,
                        $this->py->v ?? 0.0,
                        -($this->pz->v ?? 0.0)
                    );
                } else {
                    $this->v->translate(
                        $this->px->v ?? 0.0,
                        $this->py->v ?? 0.0,
                        0
                    );
                }
            } elseif ($this->p) {
                $this->v->translate(
                    $this->p->v[0] ?? 0.0,
                    $this->p->v[1] ?? 0.0,
                    -($this->p->v[2] ?? 0.0)
                );
            }
        }
        $this->frameId = $this->elem->globalData['frameId'] ?? null;

        return 0;
    }

    public function precalculateMatrix()
    {
        $this->appliedTransformations = 0;
        $this->pre->reset();

        if (count($this->a->effectsSequence ?? [])) {
            return;
        }

        if (!$this->a) {
            throw new \Error(get_class($this).": Cannot read 'a' value");
        }

        $this->pre->translate(
            -$this->a->v[0],
            -$this->a->v[1],
            $this->a->v[2] ?? 0.0
        );
        $this->appliedTransformations = 1;

        if (count($this->s->effectsSequence ?? [])) {
            return;
        }

        if (!$this->s) {
            throw new \Error(get_class($this).": Cannot read 's' value");
        }

        $this->pre->scale(
            $this->s->v[0] ?? 1.0,
            $this->s->v[1] ?? 1.0,
            $this->s->v[2] ?? 1.0
        );
        $this->appliedTransformations = 2;

        if ($this->sk) {
            if (count($this->sk->effectsSequence) > 0 || count($this->sa->effectsSequence ?? [])) {
                return;
            }
            $this->pre->skewFromAxis(-$this->sk->v, $this->sa->v ?? 0.0);
            $this->appliedTransformations = 3;
        }
        if ($this->r) {
            if (0 === count($this->r->effectsSequence)) {
                $this->pre->rotate(-$this->r->v);
                $this->appliedTransformations = 4;
            }

            return;
        }

        if (
            !count($this->rz->effectsSequence ?? [])
            && !count($this->ry->effectsSequence ?? [])
            && !count($this->rx->effectsSequence ?? [])
            && !count($this->or->effectsSequence ?? [])
        ) {
            $this->pre
                ->rotateZ(-($this->rz->v ?? 0.0))
                ->rotateY($this->ry->v ?? 0.0)
                ->rotateX($this->rx->v ?? 0.0)
                ->rotateZ(-($this->or->v[2] ?? 0.0))
                ->rotateY($this->or->v[1] ?? 0.0)
                ->rotateX($this->or->v[0] ?? 0.0)
            ;
            $this->appliedTransformations = 4;
        }
    }
    private $defaultVector = [0, 0];
}
