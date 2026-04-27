<?php

namespace LottiePHP\Elements\Helpers\Shapes;

class ProcessedElement
{
    public array $elem;
    public int $pos;

    public function __construct(array $element, int $position)
    {
        $this->elem = $element;
        $this->pos = $position;
    }
}
