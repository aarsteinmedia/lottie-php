<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Utils\General;

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
        $this->gr = General::createTag('g');
    }
}
