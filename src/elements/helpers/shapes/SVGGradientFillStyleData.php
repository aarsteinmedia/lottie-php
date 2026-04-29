<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\ShapeType;
use LottiePHP\Utils\Helpers\Constants;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\LocationHref;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\KeyframedValueProperty;
use LottiePHP\Utils\Properties\MultiDimensionalProperty;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\Properties\GradientProperty;

use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\createTag;
use function LottiePHP\Utils\Enums\lineCapEnum;
use function LottiePHP\Utils\Enums\lineJoinEnum;

class SVGGradientFillStyleData extends DynamicPropertyContainer
{
    public ?ValueProperty $a;
    public $cst = [];
    public ?MultiDimensionalProperty $e;
    public ?GradientProperty $g;
    public ?\DOMElement $gf;
    public ?\DOMElement $gr;
    public KeyframedValueProperty|ValueProperty|null $h;
    public $it = [];
    public ?string $maskId = null;
    public ?\DOMElement $ms;
    public ?ValueProperty $o;
    public ?\DOMElement $of;
    public $ost = [];
    public $prevViewData = [];
    public ?MultiDimensionalProperty $s;
    public $stops = [];
    public ?SVGStyleData $style;
    public ?array $transform;

    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        SVGStyleData $styleData,
    ) {
        $this->initDynamicPropertyContainer($elem);
        $this->initGradientData(
            $elem,
            $data,
            $styleData
        );
    }

    public function getValue(...$args)
    {
        return $this->iterateDynamicProperties(...$args);
    }

    public function initGradientData(
        BaseElement $elem,
        array $data,
        SVGStyleData $styleData
    ) {
        $this->o = PropertyFactory::getProp(
            $elem,
            $data['o'],
            0,
            0.01,
            $this
        );
        $this->s = PropertyFactory::getProp(
            $elem,
            $data['s'],
            1,
            null,
            $this
        );
        $this->e = PropertyFactory::getProp(
            $elem,
            $data['e'],
            1,
            null,
            $this
        );
        $this->h = PropertyFactory::getProp(
            $elem,
            $data['h'] ?? ['k' => 0],
            0,
            0.01,
            $this
        );
        $this->a = PropertyFactory::getProp(
            $elem,
            $data['a'] ?? ['k' => 0],
            0,
            Constants::$degToRads,
            $this
        );
        $this->g = new GradientProperty(
            $elem,
            $data['g'],
            $this
        );
        $this->style = $styleData;
        $this->stops = [];
        $this->setGradientData($styleData->pElem, $data);
        $this->setGradientOpacity($data, $styleData);
        $this->_isAnimated = (bool) $this->_isAnimated;
    }

    public function setGradientData(\DOMElement $pathElement, array $data)
    {
        $gradientId = createElementID();
        $gfill = createTag(1 === $data['t'] ? 'linearGradient' : 'radialGradient');

        $gfill->setAttribute('id', $gradientId);
        $gfill->setAttribute('spreadMethod', 'pad');
        $gfill->setAttribute('gradientUnits', 'userSpaceOnUse');
        $stops = [];
        $stop = null;
        $jLen = ($data['g']['p'] ?? 1) * 4;

        for ($j = 0; $j < $jLen; $j += 4) {
            $stop = createTag('stop');
            $gfill->appendChild($stop);
            array_push($stops, $stop);
        }

        $locationHref = LocationHref::getHref();
        $ty = ShapeType::tryFrom($data['ty'] ?? '');

        $pathElement->setAttribute(
            ShapeType::GradientFill === $ty ? 'fill' : 'stroke',
            "url({$locationHref}#{$gradientId})"
        );
        $this->gf = $gfill;
        $this->cst = $stops;
    }

    public function setGradientOpacity(array $data, SVGStyleData $styleData)
    {
        if (!$this->g?->_hasOpacity || $this->g->_collapsable) {
            return;
        }
        $stop = null;
        $mask = createTag('mask');
        $maskElement = createTag('path');

        // if (!maskElement || !mask) {
        //   throw new Error("${$this->constructor.name}: Could not create svg element")
        // }
        $mask->appendChild($maskElement);
        $opacityId = createElementID();
        $maskId = createElementID();

        $mask->setAttribute('id', $maskId);
        $opFill = createTag(1 === $data['t'] ? 'linearGradient' : 'radialGradient');

        $opFill->setAttribute('id', $opacityId);
        $opFill->setAttribute('spreadMethod', 'pad');
        $opFill->setAttribute('gradientUnits', 'userSpaceOnUse');
        $jLen
          = ($data['g']['k']['k'][0]['s'] ?? null
            ? count($data['g']['k']['k'][0]['s'] ?? [])
            : count($data['g']['k']['k'] ?? []));

        for ($j = ($data['g']['p'] ?? 1) * 4; $j < $jLen; $j += 2) {
            $stop = createTag('stop');
            $stop->setAttribute('stop-color', 'rgb(255,255,255)');
            $opFill->appendChild($stop);
            array_push($this->stops, $stop);
        }
        $locationHref = LocationHref::getHref();
        $ty = ShapeType::tryFrom($data['ty'] ?? '');
        $maskElement->setAttribute(
            ShapeType::GradientFill === $ty ? 'fill' : 'stroke',
            "url({$locationHref}#{$opacityId})"
        );
        if (ShapeType::GradientStroke === $ty) {
            $maskElement->setAttribute('stroke-linecap', lineCapEnum($data['lc'] ?? 2));
            $maskElement->setAttribute('stroke-linejoin', lineJoinEnum($data['lj'] ?? 2));
            if (1 === $data['lj']) {
                $maskElement->setAttribute('stroke-miterlimit', (int) $data['ml']);
            }
        }
        $this->of = $opFill;
        $this->ms = $mask;
        $this->ost = $this->stops;
        $this->maskId = $maskId;
        $styleData->msElem = $maskElement;
    }
}
