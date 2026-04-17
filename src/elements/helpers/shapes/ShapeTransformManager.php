<?php

namespace LottiePHP\Elements\Helpers\Shapes;

use LottiePHP\Utils\Matrix;

class ShapeTransformManager
{
    public $sequenceList = [];
    public $sequences = [];
    public $transform_key_count = 0;

    public function addTransformSequence(array $transforms)
    {
        $length = count($transforms);
        $key = '_';

        for ($i = 0; $i < $length; $i++) {

            $key += "{$transforms[$i]['transform']['key']}_";
        }
        $sequence = $this->sequences[$key];

        if (!$sequence) {
            $sequence = [
              '_mdf' => false,
              'finalTransform' => new Matrix(),
              'transforms' => [...$transforms],
            ];
            $this->sequences[$key] = $sequence;
            array($this->sequenceList, $sequence);
        }

        return $sequence;
    }

    public function getNewKey()
    {
        $this->transform_key_count++;

        return "_{$this->transform_key_count}";
    }

    public function processSequence(array $sequence, null|int|bool $isFirstFrame)
    {
        $i = 0;
        $length = count($sequence['transforms'] ?? []);

        $_mdf = $isFirstFrame;

        if (!$isFirstFrame) {
            while ($i < $length) {
                if ($sequence['transforms'][$i]['transform']['mProps']['_mdf'] ?? null) {
                    $_mdf = true;
                    break;
                }
                $i++;
            }
        }

        if ($_mdf) {
            $sequence['finalTransform']?->reset();
            for ($i = $length - 1; $i >= 0; $i -= 1) {
                [ 'v' => $matrix ] = $sequence['transforms'][$i]['transform']['mProps'] ?? [ 'v' => null ];

                if ($matrix) {
                    $sequence['finalTransform']?->multiply($matrix);
                }
            }
        }
        $sequence['_mdf'] = (bool) $_mdf;
    }

    public function processSequences(null|bool|int $isFirstFrame)
    {
        $length = count($this->sequenceList);

        for ($i = 0; $i < $length; $i++) {
            $this->processSequence($this->sequenceList[$i], $isFirstFrame);
        }
    }
}
