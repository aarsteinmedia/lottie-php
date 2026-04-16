<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Shapes\ShapePath;

use function LottiePHP\Utils\createSizedArray;

class PoolFactory
{
    private $_length = 0;
    private int $_maxLength;
    private array $pool;
    private \Closure $_create;
    private \Closure $_release;

    public function __construct(
        int $initialLength,
        callable $_create,
        ?callable $_release = null
    ) {
        $this->_maxLength = $initialLength;
        $this->_create = $_create;
        $this->_release = $_release;
        $this->pool = createSizedArray($this->_maxLength);
    }

    public function newElement()
    {
        $element = null;

        if ($this->_length) {
            $this->_length -= 1;
            $element = $this->pool[$this->_length];
        } else {
            $element = ($this->_create)();
        }

        return $element;
    }

    public function release(ShapePath|array $element)
    {
        if ($this->_length === $this->_maxLength) {
            $this->pool = double($this->pool);
            $this->_maxLength *= 2;
        }
        if ($this->_release) {
            ($this->_release)($element);
        }
        $this->pool[$this->_length] = $element;
        $this->_length++;
    }
}
