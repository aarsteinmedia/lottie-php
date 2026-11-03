<?php

namespace LottiePHP\Utils\Pooling;

use LottiePHP\Utils\General;

function double(array $arr)
{
    return [...$arr, ...General::createSizedArray(count($arr))];
}
