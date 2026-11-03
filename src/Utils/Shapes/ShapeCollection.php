<?php

namespace LottiePHP\Utils\Shapes;

use LottiePHP\Utils\General;
use LottiePHP\Utils\Pooling\ShapePool;

class ShapeCollection
{
    public float $_length = 0;
    public float $_maxLength = 4;
    public array $shapes;

    public function __construct()
    {
        $this->shapes = General::createSizedArray($this->_maxLength);
    }

    public function addShape(ShapePath $shapeData)
    {
        if ($this->_length === $this->_maxLength) {
            $this->shapes = [...$this->shapes, ...General::createSizedArray($this->_maxLength)];
            $this->_maxLength *= 2;
        }
        $this->shapes[(int) $this->_length] = $shapeData;
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
