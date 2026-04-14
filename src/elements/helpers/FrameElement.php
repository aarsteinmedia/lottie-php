<?php

namespace LottiePHP\Elements\Helpers;

use LottiePHP\Utils\Enums\PropType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;

/**
 * Handles element's layer frame update.
 * Checks layer in point and out point.
 *
 */

abstract class FrameElement extends HierarchyElement
{
    public $_mdf = false;
    public $displayStartTime = 0;

    public function addDynamicProperty(DynamicPropertyContainer $prop)
    {
        if (!in_array($prop, $this->dynamicProperties)) {
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

    public function prepareProperties(int $_number, $isVisible = false)
    {
        $length = count($this->dynamicProperties);

        for ($i = 0; $i < $length; $i++) {
            if (
                !$isVisible &&
                !($this->_isParent && $this->dynamicProperties[$i]['propType'] === PropType::Transform)
            ) {
                continue;
            }
            $this->dynamicProperties[$i]['getValue']();
            if (!$this->globalData || !$this->dynamicProperties[$i]['_mdf']) {
                continue;
            }
            $this->globalData['_mdf'] = true;
            $this->_mdf = true;
        }
    }
}
