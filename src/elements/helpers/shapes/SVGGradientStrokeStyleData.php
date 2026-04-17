<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\RendererType;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\MultiDimensionalProperty;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\Properties\DashProperty;

class SVGGradientStrokeStyleData extends SVGGradientFillStyleData
{
    public ?MultiDimensionalProperty $c;
    public DashProperty $d;
    public ?ValueProperty $w;
    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        SVGStyleData $styleData
    ) {
        parent::__construct(
            $elem,
            $data,
            $styleData
        );
        $this->initDynamicPropertyContainer($elem);
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
        ); // TODO
        $this->initGradientData(
            $elem,
            $data,
            $styleData
        );
        $this->_isAnimated = (bool) $this->_isAnimated;
    }
}
