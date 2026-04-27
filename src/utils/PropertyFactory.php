<?php

namespace LottiePHP\Utils;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\Properties\KeyframedMultidimensionalProperty;
use LottiePHP\Utils\Properties\KeyframedValueProperty;
use LottiePHP\Utils\Properties\MultiDimensionalProperty;
use LottiePHP\Utils\Properties\NoProperty;
use LottiePHP\Utils\Properties\ValueProperty;

class PropertyFactory
{
    public static function getProp(
        BaseElement|BaseProperty $elem,
        ?array $dataFromProps,
        ?int $type = null,
        ?float $mult = null,
        BaseElement|BaseProperty|DynamicPropertyContainer|null $container = null
    ) {
        $data = $dataFromProps;

        if ($data && array_key_exists('sid', $data) && $data['sid']) {
            $data = ($elem->globalData['slotManager']['getProp'] ?? null)($data);
        }
        $p = null;

        $k = $data['k'] ?? null;
        if (is_int($k) || is_float($k)) {
            $p = new ValueProperty(
                $elem,
                $data,
                $mult,
                $container
            );
        } elseif (is_array($k) && array_key_exists(0, $k) && (is_int($k[0]) || is_float($k[0]))) {
            $p = new MultiDimensionalProperty(
                $elem,
                $data,
                $mult,
                $container
            );
        } else {
            switch ($type) {
                case 0:
                    $p = new KeyframedValueProperty(
                        $elem,
                        $data,
                        $mult,
                        $container
                    );

                    break;

                case 1:
                    $p = new KeyframedMultidimensionalProperty(
                        $elem,
                        $data,
                        $mult,
                        $container
                    );

                    break;

                case null:
                default:
                    break;
            }
        }
        $p ??= new NoProperty();

        if (count($p['effectsSequence']) > 0) {
            ($container['addDynamicProperty'] ?? null)($p);
        }

        return $p;
    }
}
