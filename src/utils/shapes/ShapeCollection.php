<?php

namespace LottiePHP\Utils\Shapes;

use LottiePHP\Utils\Pooling\ShapePool;

use function LottiePHP\Utils\createSizedArray;

class ShapeCollection
{
    public $_length = 0;
    public $_maxLength = 4;
    public array $shapes;

    public function __construct()
    {
        $this->shapes = createSizedArray($this->_maxLength);
    }

    public function addShape(ShapePath $shapeData)
    {
        if ($this->_length === $this->_maxLength) {
            $this->shapes = [...$this->shapes, ...createSizedArray($this->_maxLength)];
            $this->_maxLength *= 2;
        }
        $this->shapes[$this->_length] = $shapeData;
        ++$this->_length;
    }

    public function releaseShapes()
    {
        for ($i = 0; $i < $this->_length; ++$i) {
            ShapePool::release($this->shapes[$i]);
        }
        $this->_length = 0;
    }
}
