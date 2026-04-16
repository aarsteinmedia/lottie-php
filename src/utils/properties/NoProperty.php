<?php

namespace LottiePHP\Utils\Properties;

class NoProperty extends BaseProperty
{
    public function __construct()
    {
        $this->propType = false;
    }
}
