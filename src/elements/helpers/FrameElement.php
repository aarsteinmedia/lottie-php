<?php

namespace LottiePHP\Elements\Helpers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Properties\BaseProperty;

/**
 * Handles element's layer frame update.
 * Checks layer in point and out point.
 */
abstract class FrameElement extends HierarchyElement
{
    public $_mdf = false;
    public $displayStartTime = 0;

    public function addDynamicProperty(BaseElement|BaseProperty|DynamicPropertyContainer $prop)
    {
        // Use strict identity checks: loose object comparison can recurse deeply
        // when properties contain back-references to their owning element/container.
        if (!in_array($prop, $this->dynamicProperties, true)) {
            array_push($this->dynamicProperties, $prop);
        }
    }

    public function initFrame()
    {
        // set to true when inpoint is rendered
        $this->_isFirstFrame = false;
        // list of animated properties
        $this->dynamicProperties = [];
        // If layer has been modified in current tick this will be true
        $this->_mdf = false;
    }

    public function prepareProperties(float $_number, $isVisible = false)
    {
        $length = count($this->dynamicProperties);

        for ($i = 0; $i < $length; ++$i) {
            if (
                !$isVisible
                && !($this->_isParent && PropType::Transform === $this->dynamicProperties[$i]->propType)
            ) {
                continue;
            }
            $this->dynamicProperties[$i]->getValue();
            if (!$this->globalData || !$this->dynamicProperties[$i]->_mdf) {
                continue;
            }
            $this->globalData['_mdf'] = true;
            $this->_mdf = true;
        }
    }
}
