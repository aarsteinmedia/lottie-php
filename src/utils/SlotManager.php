<?php

namespace LottiePHP\Utils;

class SlotManager
{
    public array $animationData;

    public function __construct(array $animationData)
    {
        $this->animationData = $animationData;
    }

    public function getProp(array $data)
    {
        ['sid' => $sid] = $data;

        if (
            $sid
            && ($this->animationData['slots'][$sid] ?? null)
        ) {
            $slotProp = $this->animationData['slots'][$sid]['p'] ?? [];

            if (!is_array($slotProp)) {
                return $data;
            }

            return [
                ...$data,
                ...$slotProp,
            ];
        }

        return $data;
    }
}

function slotFactory(array $animationData)
{
    return new SlotManager($animationData);
}
