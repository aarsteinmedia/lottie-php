<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Utils\Enums\ShapeType;
use LottiePHP\Utils\Shapes\Properties\ShapeBaseProperty;
use LottiePHP\Utils\Shapes\ShapeCollection;

abstract class ShapeData
{
    public ?bool $_isAnimated;
    public ?int $_length;
    public $caches = [];
    public ?SVGShapeData $data;
    public ?\DOMElement $gr;
    public ?bool $hd;

    public $it = [];
    public ?ShapeCollection $last;
    public ?ShapeCollection $localShapeCollection;
    public $lStr = '';
    public $lvl = 0;
    public $pathsData = [];
    public $prevViewData = [];
    public ?ShapeBaseProperty $sh = null;
    public ?ShapeBaseProperty $shape;
    public ?SVGStyleData $style;
    public $styledShapes = [];
    public $styles = [];
    public $tr = [];
    public ?array $transform;
    public $transformers = [];
    public ?array $transforms;
    public $trNodes = [];
    public ?ShapeType $ty;

    public function setAsAnimated()
    {
        $this->_isAnimated = true;
    }
}
