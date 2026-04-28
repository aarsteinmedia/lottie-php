<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Effects\GroupEffect;
use LottiePHP\Effects\TransformEffect;

class SVGTransformEffect extends TransformEffect
{
    public function __construct(\DOMElement $_, GroupEffect $filterManager)
    {
        $this->init($filterManager);
    }
}
