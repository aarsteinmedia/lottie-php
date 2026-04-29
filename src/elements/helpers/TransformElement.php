<?php

namespace LottiePHP\Elements\Helpers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\SVGEffects;
use LottiePHP\Utils\Enums\EffectTypes;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\Properties\TransformPropertyFactory;

abstract class TransformElement extends BaseElement
{
    public false|int $_isFirstFrame = false;
    public $hierarchy = [];
    public $localTransforms;
    public Matrix $mHelper;
    public ?SVGEffects $renderableEffectsManager;

    public function __construct()
    {
        $this->mHelper = new Matrix();
    }

    public function globalToLocal(array $point)
    {
        $pt = $point;
        $transforms = [$this->finalTransform];

        $shouldTransform = true;
        $comp = $this->comp;

        while ($shouldTransform) {
            if ($comp?->finalTransform) {
                if ($comp->data['hasMask']) {
                    array_splice($transforms, 0, 0, $comp->finalTransform);
                }
                $comp = $comp->comp;
            } else {
                $shouldTransform = false;
            }
        }
        $length = count($transforms);
        $ptNew = [0, 0, 0];

        for ($i = 0; $i < $length; ++$i) {
            $ptNew = $transforms[$i]['mat']['applyToPointArray'](
                0,
                0,
                0
            );
            $pt = [$pt[0] - (float) $ptNew[0],
                $pt[1] - (float) $ptNew[1],
                0];
        }

        return $pt;
    }

    public function initTransform()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': LottiePlayer is not initialized ');
        }
        $mat = new Matrix();

        $this->finalTransform = [
            '_localMatMdf' => false,
            '_matMdf' => false,
            '_opMdf' => false,
            'localMat' => $mat,
            'localOpacity' => 1,
            'mat' => $mat,
            'mProp' => ($this->data['ks'] ?? null)
              ? TransformPropertyFactory::getTransformProperty(
                  $this,
                  $this->data['ks'],
                  $this
              )
              : [
                  // Keep a compatible shape with TransformProperty's `o` fallback.
                  // Some lottie files omit `ks`; `o` must still be indexable (`['v']`) by render code.
                  '_mdf' => false,
                  'v' => $mat,
                  'o' => [
                      '_mdf' => false,
                      'v' => 1,
                  ],
              ],
        ];

        if ($this->data['ao'] ?? null) {
            $this->finalTransform['mProp']['autoOriented'] = true;
        }
    }

    public function renderLocalTransform()
    {
        if (!$this->localTransforms) {
            return;
        }
        if (!$this->finalTransform) {
            throw new \Error(get_class($this).': finalTransform is not initialized ');
        }
        $i = 0;
        $length = count($this->localTransforms);

        $this->finalTransform['_localMatMdf'] = $this->finalTransform['_matMdf'];

        if (!$this->finalTransform['_localMatMdf'] || !$this->finalTransform['_opMdf']) {
            while ($i < $length) {
                if ($this->localTransforms[$i]['_mdf']) {
                    $this->finalTransform['_localMatMdf'] = true;
                }
                if ($this->localTransforms[$i]['_opMdf'] && !$this->finalTransform['_opMdf']) {
                    $o = $this->finalTransform['mProp']['o'] ?? null;
                    $this->finalTransform['localOpacity'] = (float) (
                        is_object($o) ? ($o->v ?? 1) : (is_array($o) ? ($o['v'] ?? 1) : ($o ?? 1))
                    );
                    $this->finalTransform['_opMdf'] = true;
                }
                ++$i;
            }
        }
        if ($this->finalTransform['_localMatMdf']) {
            ['localMat' => $localMat, 'mat' => $mat] = $this->finalTransform;

            $this->localTransforms[0]['matrix']['clone']($localMat);
            for ($i = 1; $i < $length; ++$i) {
                $lmat = $this->localTransforms[$i]['matrix'];

                if ($lmat) {
                    $localMat->multiply($lmat);
                }
            }
            $localMat->multiply($mat);
        }
        if ($this->finalTransform['_opMdf']) {
            $localOp = (float) $this->finalTransform['localOpacity'];

            for ($i = 0; $i < $length; ++$i) {
                $localOp *= ((float) ($this->localTransforms[$i]['opacity'] ?? 100)) * 0.01;
            }
            $this->finalTransform['localOpacity'] = $localOp;
        }
    }

    public function renderTransform()
    {
        if (!$this->finalTransform) {
            throw new \Error(get_class($this).': finalTransform is not initialized ');
        }

        $o = $this->finalTransform['mProp']['o'] ?? null;
        $opMdf = false;
        if (is_object($o)) {
            $opMdf = (bool) ($o->_mdf ?? false);
        } elseif (is_array($o)) {
            $opMdf = (bool) ($o['_mdf'] ?? false);
        }
        $this->finalTransform['_opMdf'] = $opMdf || $this->_isFirstFrame;
        $this->finalTransform['_matMdf'] = (bool) ($this->finalTransform['mProp']['_mdf'] ?? null) || $this->_isFirstFrame;

        $finalMat = $this->finalTransform['mat'];
        $length = count($this->hierarchy);

        $i = 0;

        // Checking if any of the transformation matrices in the hierarchy chain has changed.
        if (!$this->finalTransform['_matMdf']) {
            while ($i < $length) {
                if ($this->hierarchy[$i]->finalTransform['mProp']['_mdf'] ?? null) {
                    $this->finalTransform['_matMdf'] = true;

                    break;
                }
                ++$i;
            }
        }

        if ($this->finalTransform['_matMdf']) {
            $mat = $this->finalTransform['mProp']['v']->props ?? null;

            if ($mat) {
                $finalMat->cloneFromProps($mat);
            }
            for ($i = 0; $i < $length; ++$i) {
                if ($this->hierarchy[$i]->finalTransform['mProp']['v'] ?? null) {
                    $matrix = $this->hierarchy[$i]->finalTransform['mProp']['v'];

                    if ($matrix) {
                        $finalMat->multiply($matrix);
                    }
                }
            }
        }
        if ($this->finalTransform['_matMdf']) {
            $this->finalTransform['_localMatMdf'] = $this->finalTransform['_matMdf'];
        }
        if ($this->finalTransform['_opMdf']) {
            $o = $this->finalTransform['mProp']['o'] ?? null;
            $opacity = 1;
            if (is_object($o)) {
                $opacity = $o->v ?? 1;
            } elseif (is_array($o)) {
                $opacity = $o['v'] ?? 1;
            } elseif (is_int($o) || is_float($o)) {
                // Some malformed data can pass opacity as a raw number.
                $opacity = $o;
            }
            $this->finalTransform['localOpacity'] = (float) ($opacity ?? 1);
        }
    }

    public function searchEffectTransforms()
    {
        if (!$this->renderableEffectsManager) {
            return;
        }
        $transformEffects = $this->renderableEffectsManager->getEffects(EffectTypes::TransformEffect);

        if (0 === count($transformEffects)) {
            return;
        }
        $this->localTransforms = [];
        if ($this->finalTransform) {
            $this->finalTransform['localMat'] = new Matrix();
        }

        $length = count($transformEffects);

        for ($i = 0; $i < $length; ++$i) {
            array_push($this->localTransforms, $transformEffects[$i]);
        }
    }
}
