<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\Helpers\Shapes\SVGShapeData;
use LottiePHP\Utils\Enums\ArrayType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;

use function LottiePHP\Utils\createTypedArray;

class GradientProperty extends DynamicPropertyContainer
{
    public $_cmdf = false;
    public bool $_collapsable;
    public int $_hasOpacity;
    public $_omdf = false;
    public array $c;
    public ?SVGShapeData $data;
    public ?bool $k;
    public array $o;
    public $prop;
    public function __construct(
        BaseElement|BaseProperty $elem,
        SVGShapeData $data,
        BaseElement|DynamicPropertyContainer $container
    ) {
        $this->data = $data;
        $this->c = createTypedArray(ArrayType::Uint8c, $data['p'] * 4);
        $cLength = $data['k']['k'][0]['s'] ?? 0
          ? (count($data['k']['k'][0]['s'] ?? []) ?? 0) - $data['p'] * 4
          : count($data['k']['k']) - $data['p'] * 4;

        $this->o = createTypedArray(ArrayType::Float32, $cLength);
        $this->_collapsable = $this->checkCollapsable();
        $this->_hasOpacity = $cLength;
        $this->initDynamicPropertyContainer($container);
        $this->prop = PropertyFactory::getProp(
            $elem,
            $data['k'],
            1,
            null,
            $this
        );
        $this->k = $this->prop['k'];
        $this->getValue(true);
    }

    public function checkCollapsable()
    {
        if (count($this->o) / 2 !== count($this->c) / 4) {
            return false;
        }
        if ($this->data['k']['k'][0]['s'] ?? null) {
            $i = 0;
            $len = count($this->data['k']['k']);

            while ($i < $len) {
                if (!$this->comparePoints($this->data['k']['k'][$i]['s'] ?? [], $this->data['p'])) {
                    return false;
                }
                $i++;
            }
        } elseif (
            !$this->comparePoints($this->data['k']['k'], $this->data['p'])
        ) {
            return false;
        }

        return true;
    }

    public function comparePoints(array $values, int $points)
    {
        $i = 0;
        $len = count($this->o) / 2;
        $diff = null;

        while ($i < $len) {
            $diff = abs(($values[$i * 4] ?? 0) - ($values[$points * 4 + $i * 2] ?? 0));
            if ($diff > 0.01) {
                return false;
            }
            $i++;
        }

        return true;
    }

    public function getValue(?bool $forceRender)
    {
        $this->prop->getValue();
        $this->_mdf = false;
        $this->_cmdf = false;
        $this->_omdf = false;
        if (!$this->prop->_mdf && !$forceRender || !is_array($this->prop->v)) {
            return 0;
        }
        $len = $this->data['p'] * 4;
        $mult = 0;
        $val = null;

        for ($i = 0; $i < $len; $i++) {
            $mult = $i % 4 === 0 ? 100 : 255;
            $val = round(($this->prop->v[$i] ?? 0) * $mult);
            if ($this->c[$i] !== $val) {
                $this->c[$i] = $val;
                $this->_cmdf = !$forceRender;
            }
        }
        if (count($this->o) > 0) {
            $length = count($this->prop->v);

            for ($i = $this->data['p'] * 4; $i < $length; $i++) {
                // mult = i % 2 === 0 ? 100 : 1
                $val = ($i % 2 === 0 ? round(($this->prop->v[$i] ?? 0) * 100) : $this->prop->v[$i]) ?? 0;
                if ($this->o[$i - $this->data['p'] * 4] !== $val) {
                    $this->o[$i - $this->data['p'] * 4] = $val;
                    $this->_omdf = !$forceRender;
                }
            }
        }
        $this->_mdf = !$forceRender;

        return 0;
    }
}
