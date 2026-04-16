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
        BaseProperty|BaseElement $elem,
        ?array $dataFromProps,
        ?int $type,
        ?int $mult,
        null|BaseProperty|BaseElement|DynamicPropertyContainer $container
    ) {
        $data = $dataFromProps;

        // console.log(data)
        if ($data && array_key_exists('sid', $data) && $data['sid']) {
            $data = ($elem['globalData']['slotManager']['getProp'] ?? null)($data);
        }
        $p = null;

        if (!count($data['k'] ?? [])) {
            $p = new ValueProperty(
                $elem,
                $data,
                $mult,
                $container
            );
        } elseif (gettype($data['k'][0] ?? null) === 'number') {
            $p = new MultiDimensionalProperty(
                $elem,
                $data,
                $mult,
                $container
            );
        } else {
            switch ($type) {
                case 0: {
                    $p = new KeyframedValueProperty(
                        $elem,
                        $data,
                        $mult,
                        $container
                    );
                    break;
                }
                case 1: {
                    $p = new KeyframedMultidimensionalProperty(
                        $elem,
                        $data,
                        $mult,
                        $container
                    );
                    break;
                }
                case null:
                default: {
                    break;
                }
            }
        }
        $p = $p ?? new NoProperty();
        if (count($p['effectsSequence']) > 0) {
            ($container['addDynamicProperty'] ?? null)($p);
        }

        return $p;
    }
}
