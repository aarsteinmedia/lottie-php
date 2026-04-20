<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\ShapeType;

use function LottiePHP\Utils\createNS;

class SVGStyleData
{
    public $_mdf = false;
    public bool $closed;
    public ?int $coOp;
    public $d = '';
    public SVGShapeData $data;
    public ?array $finalTransform;
    public ?\DOMElement $gr;
    public ?array $grd;
    public ?bool $hd;
    public ?array $it;
    public int $lvl;
    public ?\DOMElement $msElem;
    public \DOMElement $pElem;
    public ?BaseElement $prevViewData;
    public ?array $pt;
    public ?SVGStyleData $style;
    public ?int $t;
    public ?array $transform;
    public ?ShapeType $ty;
    public ?ShapeType $type;

    public function __construct(SVGShapeData $data, int $level)
    {
        $this->data = $data;
        $this->type = $data['ty'];
        $this->lvl = $level;
        $this->closed = true === $data['hd'];

        $this->pElem = createNS('path');
    }

    public function reset()
    {
        $this->d = '';
        $this->_mdf = false;
    }
}
