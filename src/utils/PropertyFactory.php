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
        mixed $dataFromProps,
        ?int $type = null,
        ?float $mult = null,
        BaseElement|BaseProperty|DynamicPropertyContainer|null $container = null
    ) {
        $data = $dataFromProps;

        // Lottie data is usually an object/array with a `k` field, but some files
        // (or upstream calls) can pass raw numeric values. Normalize those here
        // to avoid array_key_exists()/indexing on scalars.
        if (is_int($data) || is_float($data)) {
            $data = ['k' => $data];
        }

        if (is_array($data) && array_key_exists('sid', $data) && $data['sid']) {
            $data = ($elem->globalData['slotManager']?->getProp ?? null)($data);
        }
        $p = null;

        if (!$data || !is_array($data)) {
            return new NoProperty();
        }

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

        // Register animated properties so they get ticked every frame.
        // Keyframed properties (kf=true) must be added even if they have no effectsSequence.
        if ((bool) ($p['kf'] ?? false) || count($p['effectsSequence'] ?? []) > 0) {
            if ($container && isset($container['addDynamicProperty']) && is_callable($container['addDynamicProperty'])) {
                ($container['addDynamicProperty'])($p);
            } elseif (is_object($elem) && method_exists($elem, 'addDynamicProperty')) {
                $elem->addDynamicProperty($p);
            }
        }

        return $p;
    }
}
