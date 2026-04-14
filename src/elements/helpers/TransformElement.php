<?php

namespace LottiePHP\Elements\Helpers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\EffectTypes;
use LottiePHP\Utils\Matrix;

abstract class TransformElement extends BaseElement
{
    public false|int  $_isFirstFrame = false;
    public $finalTransform;
    public $hierarchy = [];
    public $localTransforms = null;
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

        for ($i = 0; $i < $length; $i++) {
            $ptNew = $transforms[$i]['mat']['applyToPointArray'](
                0,
                0,
                0
            );
            $pt = [$pt[0] - (int)$ptNew[0],
              $pt[1] - (int)$ptNew[1],
              0];
        }

        return $pt;
    }

    public function initTransform()
    {
        if (!$this->data) {
            throw new \Error(get_class($this) . ": LottiePlayer is not initialized ");
        }
        $mat = new Matrix();

        $this->finalTransform = [
          '_localMatMdf' => false,
          '_matMdf' => false,
          '_opMdf' => false,
          'localMat' => $mat,
          'localOpacity' => 1,
          'mat' => $mat,
          'mProp' => $this->data['ks']
            ? TransformPropertyFactory->getTransformProperty(
                $this,
                $this->data['ks'],
                $this
            )
            : [ 'o' => 0 ],
            ];

        if ($this->data['ao']) {
            $this->finalTransform['mProp']['autoOriented'] = true;
        }
    }

    public function renderLocalTransform()
    {
        if (!$this->localTransforms) {
            return;
        }
        if (!$this->finalTransform) {
            throw new \Error(get_class($this) . ": finalTransform is not initialized ");
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
                    $this->finalTransform['localOpacity'] = (int) $this->finalTransform['mProp']['o']['v'];
                    $this->finalTransform['_opMdf'] = true;
                }
                $i++;
            }
        }
        if ($this->finalTransform['_localMatMdf']) {
            [ 'localMat' => $localMat, 'mat' => $mat ] = $this->finalTransform;

            $this->localTransforms[0]['matrix']['clone']($localMat);
            for ($i = 1; $i < $length; $i++) {
                $lmat = $this->localTransforms[$i]['matrix'];

                if ($lmat) {
                    $localMat->multiply($lmat);
                }
            }
            $localMat->multiply($mat);
        }
        if ($this->finalTransform['_opMdf']) {
            $localOp = (int) $this->finalTransform['localOpacity'];

            for ($i = 0; $i < $length; $i++) {
                $localOp *= (int) $this->localTransforms[$i]['opacity'] ?? 1 * 0.01;
            }
            $this->finalTransform['localOpacity'] = $localOp;
        }
    }

    public function renderTransform()
    {
        if (!$this->finalTransform) {
            throw new \Error(get_class($this) . ": finalTransform is not initialized ");
        }
        $this->finalTransform['_opMdf'] = (bool) $this->finalTransform['mProp']['o']['_mdf'] || $this->_isFirstFrame;
        $this->finalTransform['_matMdf'] = (bool) $this->finalTransform['mProp']['_mdf'] || $this->_isFirstFrame;

        $finalMat = $this->finalTransform['mat'];
        $length = count($this->hierarchy);

        $i = 0;

        // Checking if any of the transformation matrices in the hierarchy chain has changed.
        if (!$this->finalTransform['_matMdf']) {
            while ($i < $length) {
                if ($this->hierarchy[$i]->finalTransform['mProp']['_mdf']) {
                    $this->finalTransform['_matMdf'] = true;
                    break;
                }
                $i++;
            }
        }

        if ($this->finalTransform['_matMdf']) {
            $mat = $this->finalTransform['mProp']['v']['props'];

            $finalMat->cloneFromProps($mat);
            for ($i = 0; $i < $length; $i++) {
                if ($this->hierarchy[$i]->finalTransform['mProp']['v']) {
                    [ 'v' => $matrix ] = $this->hierarchy[$i]->finalTransform['mProp'] ?? [ 'v' => null ];

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
            $this->finalTransform['localOpacity'] = (int) $this->finalTransform['mProp']['o']['v'];
        }
    }

    public function searchEffectTransforms()
    {
        if (!$this->renderableEffectsManager) {
            return;
        }
        $transformEffects = $this->renderableEffectsManager->getEffects(EffectTypes::TransformEffect);

        if (count($transformEffects) === 0) {
            return;
        }
        $this->localTransforms = [];
        if ($this->finalTransform) {
            $this->finalTransform['localMat'] = new Matrix();
        }

        $length = count($transformEffects);

        for ($i = 0; $i < $length; $i++) {
            array_push($this->localTransforms, $transformEffects[$i]);
        }
    }
}
