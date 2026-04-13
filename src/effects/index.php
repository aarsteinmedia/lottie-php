<?php

namespace LottiePHP\Effects;

abstract class EffectZero
{
    public ?ValueProperty $p;

    public function __construct(
        array $data,
        array $elem,
        GroupEffect $container
    ) {
        $this->p = PropertyFactory::getProp(
            $elem,
            $data['v'],
            0,
            0,
            $container
        );
    }
}

abstract class EffectOne
{
    public ?ValueProperty $p;

    public function __construct(
        array $data,
        array $elem,
        GroupEffect $container
    ) {
        $this->p = PropertyFactory::getProp(
            $elem,
            $data['v'],
            1,
            0,
            $container
        );
    }
}

class SliderEffect extends EffectZero
{
}

class AngleEffect extends EffectZero
{
}

class ColorEffect extends EffectOne
{
}

class PointEffect extends EffectOne
{
}

class LayerIndexEffect extends EffectZero
{
}

class MaskIndexEffect extends EffectZero
{
}

class CheckboxEffect extends EffectZero
{
}

class NoValueEffect
{
    public $p = [];
}
