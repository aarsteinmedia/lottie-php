<?php

namespace LottiePHP\Effects\Svg;

use function LottiePHP\Utils\createTag;

abstract class SVGComposableEffect
{
    public function createMergeNode(string $resultId, array $ins)
    {
        $feMerge = createTag('feMerge');

        $feMerge->setAttribute('result', $resultId);
        $feMergeNode = null;
        $length = count($ins);

        for ($i = 0; $i < $length; ++$i) {
            $feMergeNode = createTag('feMergeNode');
            $feMergeNode->setAttribute('in', $ins[$i]);
            $feMerge->appendChild($feMergeNode);
            $feMerge->appendChild($feMergeNode);
        }

        return $feMerge;
    }
}
