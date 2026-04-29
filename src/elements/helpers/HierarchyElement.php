<?php

namespace LottiePHP\Elements\Helpers;

/**
 * Handles AE's layer parenting property.
 */
abstract class HierarchyElement extends TransformElement
{
    public bool $_isParent = false;

    public function checkParenting()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        if (!$this->comp) {
            throw new \Error(get_class($this).': comp (ElementInterface) is not implemented');
        }
        if (!isset($this->data['parent']) || null === $this->data['parent']) {
            return;
        }

        // In the JS version this is a method reference on the comp/renderer.
        // In PHP, treat it as a normal method call (ArrayAccess only exposes properties).
        $this->comp->buildElementParenting(
            $this,
            $this->data['parent'],
            []
        );
    }

    public function initHierarchy()
    {
        // element's parent list
        $this->hierarchy = [];
        // if element is parent of another layer _isParent will be true
        $this->_isParent = false;
        $this->checkParenting();
    }

    public function setAsParent()
    {
        $this->_isParent = true;
    }

    public function setHierarchy(array $hierarchy)
    {
        $this->hierarchy = $hierarchy;
    }
}
