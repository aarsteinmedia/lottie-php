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
    private static function isMultiDimensionalArray(mixed $k): bool
    {
        if (!is_array($k) || !array_key_exists(0, $k) || is_array($k[0])) {
            return false;
        }

        return count($k) >= 2 && is_numeric($k[0] ?? null);
    }

    private static function isUniDimensionalArray(mixed $k): bool
    {
        return is_array($k)
            && 1 === count($k)
            && is_numeric($k[0] ?? null)
            && !is_array($k[0]);
    }

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
            $slotManager = $elem->globalData['slotManager'] ?? null;
            if (is_object($slotManager) && method_exists($slotManager, 'getProp')) {
                $data = $slotManager->getProp($data);
            } elseif (is_object($slotManager) && isset($slotManager->getProp) && is_callable($slotManager->getProp)) {
                $data = ($slotManager->getProp)($data);
            }
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
        } elseif (self::isUniDimensionalArray($k)) {
            // Some exports wrap scalar props as single-element arrays (e.g. `"k": [0]`).
            $data['k'] = $k[0];
            $p = new ValueProperty(
                $elem,
                $data,
                $mult,
                $container
            );
        } elseif (self::isMultiDimensionalArray($k)) {
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
        //
        // Important: `$container` can be an object (e.g. TransformProperty) that composes child
        // properties into a derived value. Prefer registering the dynamic property on the
        // container when possible, otherwise the container won't recompute its composed value.
        if ((bool) ($p['kf'] ?? false) || count($p['effectsSequence'] ?? []) > 0) {
            if (is_object($container) && method_exists($container, 'addDynamicProperty')) {
                $container->addDynamicProperty($p);
            } elseif (
                $container
                && isset($container['addDynamicProperty'])
                && is_callable($container['addDynamicProperty'])
            ) {
                ($container['addDynamicProperty'])($p);
            } elseif (is_object($elem) && method_exists($elem, 'addDynamicProperty')) {
                $elem->addDynamicProperty($p);
            }
        }

        return $p;
    }
}
