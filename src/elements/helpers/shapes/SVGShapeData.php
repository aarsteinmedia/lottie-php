<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Utils\Shapes\Properties\ShapeBaseProperty;

class SVGShapeData extends ShapeData
{
    public function __construct(
        array $transformers,
        int $level,
        ShapeBaseProperty $shape
    ) {
        $this->caches = [];
        $this->styles = [];
        $this->transformers = $transformers;
        $this->lStr = '';
        $this->sh = $shape;
        $this->lvl = $level;
        // TODO: find if there are some cases where _isAnimated can be false.
        // For now, since shapes add up with other shapes. They have to be calculated every time.
        // One way of finding out is checking if all styles associated to $this shape depend only of $this shape
        $this->_isAnimated = (bool) $shape['k'];
        // TODO: commenting $this for now since all shapes are animated
        $i = 0;
        $length = count($transformers);

        while ($i < $length) {
            if (count($transformers[$i]['mProps']->dynamicProperties ?? []) > 0) {
                $this->_isAnimated = true;

                break;
            }
            ++$i;
        }
    }
}
