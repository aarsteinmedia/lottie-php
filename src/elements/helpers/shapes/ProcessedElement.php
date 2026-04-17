<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;

class ProcessedElement
{
    public BaseElement $elem;
    public int $pos;
    public function __construct(BaseElement $element, int $position)
    {
        $this->elem = $element;
        $this->pos = $position;
    }
}
