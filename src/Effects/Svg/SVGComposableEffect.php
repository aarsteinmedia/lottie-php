<?php

namespace LottiePHP\Effects\Svg;

use LottiePHP\Utils\General;

abstract class SVGComposableEffect
{
    public function createMergeNode(string $resultId, array $ins)
    {
        $feMerge = General::createTag('feMerge');

        $feMerge->setAttribute('result', $resultId);
        $feMergeNode = null;
        $length = count($ins);

        for ($i = 0; $i < $length; ++$i) {
            $feMergeNode = General::createTag('feMergeNode');
            $feMergeNode->setAttribute('in', $ins[$i]);
            $feMerge->appendChild($feMergeNode);
            $feMerge->appendChild($feMergeNode);
        }

        return $feMerge;
    }
}
