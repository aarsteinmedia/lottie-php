<?php

namespace LottiePHP\Effects;

use LottiePHP\Utils\Enums\EffectTypes;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\Properties\ValueProperty;

abstract class TransformEffect
{
    public ?bool $_mdf;
    public ?bool $_opMdf;
    public ?GroupEffect $effectsManager;
    public ?Matrix $matrix;
    public ?ValueProperty $op;
    public $opacity = 1;
    public ?EffectTypes $type;

    public function init(GroupEffect $effectsManager)
    {
        $this->effectsManager = $effectsManager;
        $this->type = EffectTypes::TransformEffect;
        $this->matrix = new Matrix();
        $this->opacity = -1;
        $this->_mdf = false;
        $this->_opMdf = false;
    }

    public function renderFrame(?bool $forceFrame)
    {
        $this->_opMdf = false;
        $this->_mdf = false;
        if (!$forceFrame && !$this->effectsManager?->_mdf) {
            return;
        }
        $effectElements = $this->effectsManager['effectElements'] ?? [];
        $anchor = $effectElements[0]['p']->v ?? null;
        $position = $effectElements[1]['p']->v ?? null;
        $isUniformScale = ($effectElements[2]['p']->v ?? 0) === 1;
        $scaleHeight = $effectElements[3]['p']->v ?? 0;
        $scaleWidth = $isUniformScale
          ? $scaleHeight
          : ($effectElements[4]['p']->v ?? 0);
        $skew = $effectElements[5]['p']->v ?? 0;
        $skewAxis = $effectElements[6]['p']->v ?? 0;
        $rotation = $effectElements[7]['p']->v ?? 0;

        $this->matrix?->reset();
        $this->matrix?->translate(
            -$anchor[0],
            -$anchor[1],
            $anchor[2]
        );
        $this->matrix?->scale(
            $scaleWidth * 0.01,
            $scaleHeight * 0.01,
            1
        );
        $this->matrix?->rotate(-$rotation * Constants::$degToRads);
        $this->matrix?->skewFromAxis(-$skew * Constants::$degToRads, ($skewAxis + 90) * Constants::$degToRads);
        $this->matrix?->translate(
            $position[0],
            $position[1],
            0
        );
        $this->_mdf = true;
        if ($this->opacity !== $effectElements[8]['p']->v ?? null) {
            $this->opacity = $effectElements[8]['p']->v ?? null;
            $this->_opMdf = true;
        }
    }
}
