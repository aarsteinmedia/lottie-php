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
    public array $data;
    public ?array $finalTransform;
    public ?\DOMElement $gr = null;
    public ?array $grd = null;
    public ?bool $hd = null;
    public ?array $it = null;
    public int $lvl;
    public ?\DOMElement $msElem = null;
    public \DOMElement $pElem;
    public ?BaseElement $prevViewData = null;
    public ?array $pt = null;
    public ?SVGStyleData $style = null;
    public ?int $t = null;
    public ?array $transform = null;
    public ?ShapeType $ty = null;
    public ?ShapeType $type;

    public function __construct(
        array $data,
        int $level
    ) {
        $this->data = $data;
        $this->type = ShapeType::tryFrom($data['ty'] ?? '');
        $this->lvl = $level;
        // "closed" here is used by style renderers to decide path closing behavior.
        $this->closed = ($data['closed'] ?? false) === true;

        $this->pElem = createNS('path');
    }

    public function reset()
    {
        $this->d = '';
        $this->_mdf = false;
    }
}
