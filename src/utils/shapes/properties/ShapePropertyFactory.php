<?php

namespace LottiePHP\Utils\Shapes\Properties;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Elements\ShapeElement;
use LottiePHP\Utils\Properties\KeyframedValueProperty;

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
            case 4: {
                $dataProp = $type === 3 ? $data['pt'] : $data['ks'];
                $keys = $dataProp['k'] ?? 0;

                if (count($keys ?? [])) {
                    $prop = new KeyframedValueProperty(
                        $elem,
                        $data,
                        $type
                    );
                    break;
                }
                $prop = new ShapeProperty(
                    $elem,
                    $data,
                    $type
                );
                break;
            }
            case 5: {
                $prop = new RectShapeProperty($elem, $data);
                break;
            }
            case 6: {
                $prop = new EllShapeProperty($elem, $data);
                break;
            }
            case 7: {
                $prop = new StarShapeProperty($elem, $data);
            }
        }

        if ($prop['k'] ?? 0) {
            $elem->addDynamicProperty($prop);
        }

        return $prop;
    }
}
