<?php

namespace LottiePHP\Utils\Pooling;

use function LottiePHP\Utils\createSizedArray;

function double(array $arr)
{
    return [...$arr, ...createSizedArray(count($arr))];
}
