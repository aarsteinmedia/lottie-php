<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\Shapes\ShapePath;

use function LottiePHP\Utils\createSizedArray;

class PoolFactory
{
    public function __construct(
        int $initialLength,
        callable $_create,
        ?callable $_release = null
    ) {
        $this->_maxLength = $initialLength;
        $this->_create = \Closure::fromCallable($_create);
        $this->_release = $_release ? \Closure::fromCallable($_release) : null;
        $this->pool = createSizedArray($this->_maxLength);
    }

    public function newElement()
    {
        $element = null;

        if ($this->_length) {
            --$this->_length;
            $element = $this->pool[$this->_length];
        } else {
            $element = ($this->_create)();
        }

        return $element;
    }

    public function release(array|ShapePath $element)
    {
        if ($this->_length === $this->_maxLength) {
            $this->pool = double($this->pool);
            $this->_maxLength *= 2;
        }
        if ($this->_release) {
            ($this->_release)($element);
        }
        $this->pool[$this->_length] = $element;
        ++$this->_length;
    }
    private \Closure $_create;
    private $_length = 0;
    private int $_maxLength;
    private ?\Closure $_release = null;
    private array $pool;
}
