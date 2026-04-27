<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableDOMElement;
use LottiePHP\Utils\Properties\BaseProperty;

abstract class CompElement extends RenderableDOMElement
{
    use CompTrait;

    public ?bool $completeLayers;
    public $currentFrame = 0;
    public $elements = [];
    public $layers = [];
    public ?BaseProperty $tm;
}
