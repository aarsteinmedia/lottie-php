<?php

namespace LottiePHP\Effects;

class EffectsManager
{
    public ?bool $_mdf;
    public array $effectElements = [];
    public function __construct(
        array $data,
        array $element,
        ?array $_dynamicProperties = null
    ) {
        $effects = $data['ef'] ?? [];
        $length = count($effects);

        for ($i = 0; $i < $length; $i++) {
            $effectItem = new GroupEffect(
                $effects[$i],
                $element,
                $data
            );

            array_push($this->effectElements, $effectItem);
        }
    }
}
