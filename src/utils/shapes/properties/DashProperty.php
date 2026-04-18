<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Enums\RendererType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;

use function LottiePHP\Utils\createSizedArray;
use function LottiePHP\Utils\createTypedArray;

class DashProperty extends DynamicPropertyContainer
{
    public array $dashArray;
    public array $dashoffset;
    public $dashStr = '';
    public array $dataProps;
    public BaseElement|BaseProperty $elem;
    public $frameId = -1;
    public $k = false;
    public RendererType $renderer;

    public function __construct(
        BaseElement|BaseProperty $elem,
        array $data,
        RendererType $renderer,
        DynamicPropertyContainer $container
    ) {
        $this->dashoffset = createTypedArray(ArrayType::Float32, 1);
        $this->elem = $elem;
        $dataLength = count($data);
        $this->dataProps = createSizedArray($dataLength);
        $this->renderer = $renderer;
        $this->dashArray = createTypedArray(
            ArrayType::Float32,
            $dataLength > 0 ? $dataLength - 1 : 0
        );
        $this->initDynamicPropertyContainer($container);
        $len = $dataLength || 0;
        $prop = null;

        for ($i = 0; $i < $len; ++$i) {
            $prop = PropertyFactory::getProp(
                $elem,
                $data[$i]['v'] ?? null,
                0,
                0,
                $this
            );
            $this->k = $prop['k'] || $this->k;
            $this->dataProps[$i] = [
                'n' => $data[$i]['n'] ?? 'd',
                'p' => $prop,
            ];
        }
        if (!$this->k) {
            $this->getValue(true);
        }
        $this->_isAnimated = $this->k;
    }

    public function getValue(?bool $forceRender)
    {
        if ($this->elem->globalData['frameId'] === $this->frameId && !$forceRender) {
            return 0;
        }
        if ($this->elem->globalData['frameId']) {
            $this->frameId = $this->elem->globalData['frameId'];
        }

        $this->iterateDynamicProperties();
        $this->_mdf = $this->_mdf || (bool) $forceRender;
        if (!$this->_mdf) {
            return 0;
        }
        $len = count($this->dataProps);

        if (RendererType::SVG === $this->renderer) {
            $this->dashStr = '';
        }
        for ($i = 0; $i < $len; ++$i) {
            if (($this->dataProps[$i]['n'] ?? '') === 'o') {
                $this->dashoffset[0] = $this->dataProps[$i]['p']['v'] ?? 0;

                continue;
            }
            if (RendererType::SVG === $this->renderer) {
                $value = $this->dataProps[$i]['p']['v'] ?? '';

                $this->dashStr .= " {$value}";

                continue;
            }

            $this->dashArray[$i] = $this->dataProps[$i]['p']['v'] ?? null;
        }

        return 0;
    }
}
