<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

class ShapePropertyFactory
{
    public static function getConstructorFunction()
    {
        return ShapeProperty::class;
    }

    public static function getKeyframeConstructorFunction()
    {
        return KeyframeShapeProperty::class;
    }

    public static function getShapeProp(
        BaseElement|DynamicPropertyContainer $elem,
        array $data,
        int $type,
        ?array $_arr = null,
        ?array $_trims = null
    ) {
        $prop = null;

        switch ($type) {
            case 3:
            case 4:
                $dataProp = 3 === $type ? $data['pt'] : $data['ks'];
                $keys = $dataProp['k'] ?? null;

                // Only treat as keyframed when entries look like keyframes (`t`/`s`).
                // Static path data is also array-shaped and must stay on ShapeProperty.
                if (
                    is_array($keys)
                    && count($keys) > 0
                    && is_array($keys[0] ?? null)
                    && (
                        array_key_exists('t', $keys[0])
                        || array_key_exists('s', $keys[0])
                    )
                ) {
                    $prop = new KeyframeShapeProperty($elem, $data, $type);

                    break;
                }
                $prop = new ShapeProperty(
                    $elem,
                    $data,
                    $type
                );

                break;

            case 5:
                $prop = new RectShapeProperty($elem, $data);

                break;

            case 6:
                $prop = new EllShapeProperty($elem, $data);

                break;

            case 7:
                $prop = new StarShapeProperty($elem, $data);
        }

        if (($prop->k ?? false) === true) {
            $elem->addDynamicProperty($prop);
        }

        return $prop;
    }
}
