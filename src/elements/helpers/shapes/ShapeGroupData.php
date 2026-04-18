<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use function LottiePHP\Utils\createNS;

class ShapeGroupData
{
    public ?bool $_shouldRender;
    public ?bool $closed;
    public \DOMElement $gr;
    public $it = [];
    public $prevViewData = [];
    public ?array $transform;

    public function __construct()
    {
        $this->gr = createNS('g');
    }
}
