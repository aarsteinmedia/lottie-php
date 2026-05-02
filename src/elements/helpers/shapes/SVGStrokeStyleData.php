<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\RendererType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\Properties\DashProperty;

class SVGStrokeStyleData extends SVGFillStyleData
{
    public DashProperty $d;

    public function __construct(
        BaseElement|DynamicPropertyContainer $elem,
        array $data,
        SVGStyleData $styleObj
    ) {
        parent::__construct(
            $elem,
            $data,
            $styleObj
        );
        $this->initDynamicPropertyContainer($elem);
        $this->o = PropertyFactory::getProp(
            $elem,
            $data['o'],
            0,
            0.01,
            $this
        );
        $this->w = PropertyFactory::getProp(
            $elem,
            $data['w'],
            0,
            null,
            $this
        );
        $this->d = new DashProperty(
            $elem,
            $data['d'] ?? [],
            RendererType::SVG,
            $this
        );
        $this->c = PropertyFactory::getProp(
            $elem,
            $data['c'],
            1,
            255,
            $this
        );
        $this->style = $styleObj;
        $this->_isAnimated = (bool) $this->_isAnimated;
    }

    public function getValue(?bool $flag = null)
    {
        return $this->iterateDynamicProperties($flag);
    }
}
