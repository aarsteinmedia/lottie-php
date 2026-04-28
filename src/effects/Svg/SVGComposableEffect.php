<?php

namespace LottiePHP\Effects\Svg;

use function LottiePHP\Utils\createNS;

abstract class SVGComposableEffect
{
    public function createMergeNode(string $resultId, array $ins)
    {
        $feMerge = createNS('feMerge');

        $feMerge->setAttribute('result', $resultId);
        $feMergeNode = null;
        $length = count($ins);

        for ($i = 0; $i < $length; ++$i) {
            $feMergeNode = createNS('feMergeNode');
            $feMergeNode->setAttribute('in', $ins[$i]);
            $feMerge->appendChild($feMergeNode);
            $feMerge->appendChild($feMergeNode);
        }

        return $feMerge;
    }
}
