<?php

namespace LottiePHP\Elements\Helpers\Shapes;

class ProcessedElement
{
    public array $elem;
    public int $pos;

    public function __construct(array $elem, int $position)
    {
        $this->elem = $elem;
        $this->pos = $position;
    }
}
