<?php

namespace LottiePHP\Elements\Helpers\Shapes;

class ProcessedElement
{
    public string $key;
    public int $pos;

    public function __construct(string $key, int $position)
    {
        $this->key = $key;
        $this->pos = $position;
    }
}
