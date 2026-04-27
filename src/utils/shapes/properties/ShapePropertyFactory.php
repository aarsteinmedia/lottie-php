<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\ShapeElement;

class ShapePropertyFactory
{
    public static function getConstructorFunction()
    {
        return ShapeProperty::class;
    }

    public static function getKeyframedConstructorFunction()
    {
        return KeyframedShapeProperty::class;
    }

    public static function getShapeProp(
        ShapeElement $elem,
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

                // When `k` is an array of keyframes, we need the keyframed shape property.
                if (is_array($keys) && count($keys) > 0 && is_array($keys[0] ?? null)) {
                    $prop = new KeyframedShapeProperty($elem, $data, $type);

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
