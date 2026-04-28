<?php

namespace LottiePHP\Utils\Text;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Bezier;
use LottiePHP\Utils\Enums\RendererType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\Properties\BaseProperty;
use LottiePHP\Utils\PropertyFactory;

use function LottiePHP\Utils\createSizedArray;
use function LottiePHP\Utils\toRGBString;

class TextAnimatorProperty extends DynamicPropertyContainer
{
    public $_frameId = -1;
    public $_isFirstFrame = true;
    public $defaultPropsArray = [];
    public $lettersChangedFlag = false;
    public $renderedLetters = [];

    private static function addBrightnessToRGB(array $color, int $offset)
    {
        $hsv = self::rgbToHSV(
            $color[0] * 255,
            $color[1] * 255,
            $color[2] * 255
        );

        $hsv[2] += $offset;
        if ($hsv[2] > 1) {
            $hsv[2] = 1;
        } elseif ($hsv[2] < 0) {
            $hsv[2] = 0;
        }

        return self::HSVtoRGB(
            $hsv[0],
            $hsv[1],
            $hsv[2]
        );
    }

    private static function addHueToRGB(array $color, int $offset)
    {
        $hsv = self::rgbToHSV(
            $color[0] * 255,
            $color[1] * 255,
            $color[2] * 255
        );

        $hsv[0] += $offset / 360;
        if ($hsv[0] > 1) {
            --$hsv[0];
        } elseif ($hsv[0] < 0) {
            ++$hsv[0];
        }

        return self::HSVtoRGB(
            $hsv[0],
            $hsv[1],
            $hsv[2]
        );
    }

    private static function addSaturationToRGB(array $color, int $offset)
    {
        $hsv = self::rgbToHSV(
            $color[0] * 255,
            $color[1] * 255,
            $color[2] * 255
        );

        $hsv[1] += $offset;
        if ($hsv[1] > 1) {
            $hsv[1] = 1;
        } elseif ($hsv[1] <= 0) {
            $hsv[1] = 0;
        }

        return self::HSVtoRGB(
            $hsv[0],
            $hsv[1],
            $hsv[2]
        );
    }

    private static function HSVtoRGB(
        int $h,
        int $s,
        int $v
    ) {
        $r = 0;
        $g = 0;
        $b = 0;
        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;

                break;

            case 1:
                $r = $q;
                $g = $v;
                $b = $p;

                break;

            case 2:
                $r = $p;
                $g = $v;
                $b = $t;

                break;

            case 3:
                $r = $p;
                $g = $q;
                $b = $v;

                break;

            case 4:
                $r = $t;
                $g = $p;
                $b = $v;

                break;

            case 5:
                $r = $v;
                $g = $p;
                $b = $q;

                break;

            default:
                break;
        }

        return [
            $r,
            $g,
            $b,
        ];
    }

    private static function rgbToHSV(
        int $r,
        int $g,
        int $b
    ) {
        $max = max(
            $r,
            $g,
            $b
        );
        $min = min(
            $r,
            $g,
            $b
        );
        $d = $max - $min;
        $h = 0;
        $s = 0 === $max ? 0 : $d / $max;
        $v = $max / 255;

        switch ($max) {
            case $min:
                $h = 0;

                break;

            case $r:
                $h = $g - $b + $d * ($g < $b ? 6 : 0);
                $h /= 6 * $d;

                break;

            case $g:
                $h = $b - $r + $d * 2;
                $h /= 6 * $d;

                break;

            case $b:
                $h = $r - $g + $d * 4;
                $h /= 6 * $d;

                break;

            default:
                break;
        }

        return [
            $h,
            $s,
            $v,
        ];
    }

    public function __construct(
        array $textData,
        RendererType $renderType,
        BaseElement|BaseProperty $elem
    ) {
        $this->mHelper = new Matrix();
        $this->_textData = $textData;
        $this->_renderType = $renderType;
        $this->_elem = $elem;
        $this->_animatorsData = createSizedArray((int) count($this->_textData['a']));
        $this->initDynamicPropertyContainer($elem);
    }

    public function getMeasures(array $documentData, ?bool $lettersChangedFlag)
    {
        try {
            $this->{$lettersChangedFlag} = (bool) $lettersChangedFlag;
            if (
                !$this->_mdf
                && !$this->_isFirstFrame
                && !$lettersChangedFlag
                && (!$this->_hasMaskedPath || !$this->_pathData['m']['_mdf'] ?? null)
            ) {
                return;
            }
            $this->_isFirstFrame = false;
            $alignment = $this->_moreOptions['alignment']['v'];
            $animators = $this->_animatorsData;
            $textData = $this->_textData;
            $matrixHelper = $this->mHelper;
            $renderType = $this->_renderType;

            $renderedLettersCount = count($this->renderedLetters);
            $xPos = null;
            $yPos = null;
            $i = 0;
            $len = 0;
            $letters = $documentData['l'];

            $pathInfo = null;
            $currentLength = 0;
            $currentPoint = null;
            $segmentLength = 0;
            $shouldMeasure = false;
            $pointInd = 0;
            $segmentInd = 0;
            $prevPoint = null;
            $points = null;
            $segments = [];
            $partialLength = 0;
            $totalLength = 0;
            $perc = 0;
            $tanAngle = null;
            $mask = null;

            if ($this->_hasMaskedPath && $this->_pathData['m']) {
                $mask = $this->_pathData['m'];
                if (!$this->_pathData['n'] || $this->_pathData['_mdf']) {
                    $paths = $mask->v;

                    if ($paths) {
                        if ($this->_pathData['r']->v ?? null) {
                            $paths = array_reverse($paths);
                        }
                        $pathInfo = [
                            'segments' => [],
                            'tLength' => 0,
                        ];
                        $len = $paths['_length'] - 1;
                        $bezierData = null;

                        $totalLength = 0;
                        for ($i = 0; $i < $len; ++$i) {
                            if (is_array($paths)) {
                                continue;
                            }
                            $bezierData = Bezier::buildBezierData(
                                $paths->v[$i],
                                $paths->v[$i + 1],
                                [($paths['o'][$i][0] ?? 0) - ($paths->v[$i][0] ?? 0), ($paths['o'][$i][1] ?? 0) - ($paths->v[$i][1] ?? 0)],
                                [
                                    ($paths['i'][$i + 1][0] ?? 0) - ($paths->v[$i + 1][0] ?? 0), ($paths['i'][$i + 1][1] ?? 0) - ($paths->v[$i + 1][1] ?? 0),
                                ]
                            );
                            $pathInfo['tLength'] += $bezierData['segmentLength'];
                            array_push($pathInfo['segments'], $bezierData);
                            $totalLength += $bezierData['segmentLength'];
                        }
                        $i = $len;
                        if (($mask->v['c'] ?? null) && !is_array($paths)) {
                            $bezierData = Bezier::buildBezierData(
                                $paths->v[$i],
                                $paths->v[0],
                                [($paths['o'][$i][0] ?? 0) - ($paths->v[$i][0] ?? 0), ($paths['o'][$i][1] ?? 0) - ($paths->v[$i][1] ?? 0)],
                                [($paths['i'][0][0] ?? 0) - ($paths->v[0][0] ?? 0), ($paths['i'][0][1] ?? 0) - ($paths->v[0][1] ?? 0)]
                            );
                            $pathInfo['tLength'] += $bezierData['segmentLength'];
                            array_push($pathInfo['segments'], $bezierData);
                            $totalLength += $bezierData['segmentLength'];
                        }
                        $this->_pathData['pi'] = $pathInfo;
                    }
                }
                $pathInfo = $this->_pathData['pi'];

                $currentLength = $this->_pathData['f']->v ?? 0;
                $segmentInd = 0;
                $pointInd = 1;
                $segmentLength = 0;
                // $shouldMeasure = true
                $segments = $pathInfo['segments'] ?? [];

                if ($currentLength > 0 && $mask->v['c'] ?? null) {
                    if ($pathInfo['tLength'] ?? 0 < abs($currentLength)) {
                        $currentLength = -abs($currentLength) % ($pathInfo['tLength'] ?? 0);
                    }
                    $segmentInd = count($segments) - 1;
                    $points = $segments[$segmentInd]['points'] ?? [];
                    $pointInd = count($points) - 1;

                    while ($currentLength < 0) {
                        $currentLength += $points[$pointInd]['partialLength'] ?? 0;
                        --$pointInd;
                        if ($pointInd < 0) {
                            --$segmentInd;
                            $points = $segments[$segmentInd]['points'] ?? [];
                            $pointInd = count($points) - 1;
                        }
                    }
                }

                $points = $segments[$segmentInd]['points'] ?? [];
                $prevPoint = $points[$pointInd - 1];
                $currentPoint = $points[$pointInd];
                $partialLength = $currentPoint['partialLength'] ?? 0;
            }

            $len = count($letters ?? []);
            $xPos = 0;
            $yPos = 0;
            $yOff = ($documentData['finalSize'] || 0) * 1.2 * 0.714;
            $isFirstLine = true;
            $animatorProps = null;
            $animatorSelector = null;
            $j = 0;
            $letterValue = null;

            $jLen = count($animators);

            $mult = null;
            $ind = -1;
            $offf = null;
            $xPathPos = 0;
            $yPathPos = 0;
            $initPathPos = $currentLength;
            $initSegmentInd = $segmentInd;
            $initPointInd = $pointInd;
            $currentLine = -1;
            $elemOpacity = null;
            $sc = [0,
                0,
                0];
            $sw = 0;
            $fc = [0,
                0,
                0];
            $k = 0;
            $letterSw = null;
            $letterSc = null;
            $letterFc = null;
            $letterM = '';
            $letterP = $this->defaultPropsArray;
            $letterO = null;

            if (2 === $documentData['j'] || 1 === $documentData['j']) {
                $animatorJustifyOffset = 0;
                $animatorFirstCharOffset = 0;
                $justifyOffsetMult = 2 === $documentData['j'] ? -0.5 : -1;
                $lastIndex = 0;
                $isNewLine = true;

                for ($i = 0; $i < $len; ++$i) {
                    if ($letters[$i]['n']) {
                        if ($animatorJustifyOffset) {
                            $animatorJustifyOffset += $animatorFirstCharOffset;
                        }
                        while ($lastIndex < $i) {
                            $letters[$lastIndex]['animatorJustifyOffset'] = $animatorJustifyOffset;
                            ++$lastIndex;
                        }
                        $animatorJustifyOffset = 0;
                        $isNewLine = true;

                        continue;
                    }
                    for ($j = 0; $j < $jLen; ++$j) {
                        $animatorProps = $animators[$j]['a'] ?? [];
                        if (!$animatorProps['t']['propType'] ?? null) {
                            continue;
                        }
                        ['v' => $animVal] = $animatorProps['t'];

                        if ('number' !== gettype($animVal)) {
                            continue;
                        }

                        if ($isNewLine && 2 === $documentData['j']) {
                            $animatorFirstCharOffset += $animVal * $justifyOffsetMult;
                        }
                        $animatorSelector = $animators[$j]['s'] ?? null;
                        $mult = $animatorSelector?->getMult(
                            $letters[$i]['anIndexes'][$j] ?? 0,
                            $textData['a'][$j]['s']['totalChars'] ?? 0
                        );

                        if (null !== $mult) {
                            if (is_array($mult)) {
                                $animatorJustifyOffset
                                  += $animVal * ($mult[0] ?? 1) * $justifyOffsetMult;
                            } else {
                                $animatorJustifyOffset
                                  += $animVal * $mult * $justifyOffsetMult;
                            }
                        }
                    }
                    $isNewLine = false;
                }
                if ($animatorJustifyOffset) {
                    $animatorJustifyOffset += $animatorFirstCharOffset;
                }
                while ($lastIndex < $i) {
                    $letters[$lastIndex]['animatorJustifyOffset'] = $animatorJustifyOffset;
                    ++$lastIndex;
                }
            }

            for ($i = 0; $i < $len; ++$i) {
                $matrixHelper->reset();
                $elemOpacity = 1;
                if ($letters[$i]['n'] ?? null) {
                    $xPos = 0;
                    $yPos += $documentData['yOffset'] || 0;
                    $yPos += $isFirstLine ? 1 : 0;
                    $currentLength = $initPathPos;
                    $isFirstLine = false;
                    if ($this->_hasMaskedPath) {
                        $segmentInd = $initSegmentInd || 0;
                        $pointInd = $initPointInd || 0;
                        $points = $segments[$segmentInd]['points'] ?? [];
                        $prevPoint = $points[$pointInd - 1];
                        $currentPoint = $points[$pointInd];
                        $partialLength = $currentPoint['partialLength'] ?? 0;
                        $segmentLength = 0;
                    }
                    $letterM = '';
                    $letterFc = '';
                    $letterSw = '';
                    $letterO = '';
                    $letterP = $this->defaultPropsArray;
                } else {
                    if ($this->_hasMaskedPath) {
                        if ($currentLine !== $letters[$i]['line'] ?? null) {
                            switch ($documentData['j']) {
                                case 1:
                                    $currentLength
                                    += $totalLength - ($documentData['lineWidths'][$letters[$i]['line'] ?? 0] ?? 0);

                                    break;

                                case 2:
                                    $currentLength
                                      += ($totalLength - ($documentData['lineWidths'][$letters[$i]['line'] ?? 0] ?? 0)) / 2;

                                    break;

                                case null:
                                default:
                                    break;
                            }
                            $currentLine = $letters[$i]['line'] ?? 0;
                        }
                        if ($ind !== $letters[$i]['ind'] ?? null) {
                            if ($letters[$ind]) {
                                $currentLength += (int) ($letters[$ind]['extra'] ?? 0);
                            }
                            $currentLength += ($letters[$i]['an'] ?? 0) / 2;
                            $ind = $letters[$i]['ind'] ?? 0;
                        }
                        $currentLength += $alignment[0] * ($letters[$i]['an'] ?? 0) * 0.005;
                        $animatorOffset = 0;

                        for ($j = 0; $j < $jLen; ++$j) {
                            $animatorProps = $animators[$j]['a'] ?? null;
                            if ($animatorProps['p']['propType'] ?? null) {
                                $animatorSelector = $animators[$j]['s'] ?? null;
                                $mult = $animatorSelector?->getMult(
                                    $letters[$i]['anIndexes'][$j] ?? 0,
                                    $textData['a'][$j]['s']['totalChars'] ?? 0
                                );
                                if (!$mult) {
                                    continue;
                                }
                                if (is_array($animatorProps['p']->v)) {
                                    if (is_array($mult)) {
                                        $animatorOffset += $animatorProps['p']->v[0] * ($mult[0] ?? 1);
                                    } else {
                                        $animatorOffset += $animatorProps['p']->v[0] * $mult;
                                    }
                                }
                            }
                            if (!$animatorProps['a']['propType'] ?? null) {
                                continue;
                            }
                            $animatorSelector = $animators[$j]['s'] ?? null;
                            $mult = $animatorSelector?->getMult(
                                (int) ($letters[$i]['anIndexes'][$j] ?? 0),
                                $textData['a'][$j]['s']['totalChars'] ?? 0
                            );
                            if (!$mult) {
                                continue;
                            }
                            if (!is_array($animatorProps['a']->v)) {
                                continue;
                            }
                            if (is_array($mult)) {
                                $animatorOffset += ($animatorProps['a']->v[0] ?? 0) * ($mult[0] ?? 1);

                                continue;
                            }
                            $animatorOffset += ($animatorProps['a']->v[0] ?? 0) * $mult;
                        }
                        $shouldMeasure = true;

                        // Force alignment only works with a single line for now
                        if ($this->_pathData['a']->v ?? null) {
                            $currentLength
                            = ($letters[0]['an'] ?? 0) * 0.5
                            + ($totalLength
                              - (int) ($this->_pathData['f']->v ?? null)
                              - ($letters[0]['an'] ?? 0) * 0.5
                              - ($letters[count($letters) - 1]['an'] ?? 0) * 0.5)
                              * $ind
                              / ($len - 1);
                            $currentLength += (int) ($this->_pathData['f']->v ?? 0);
                        }
                        while ($shouldMeasure) {
                            if (
                                $segmentLength + $partialLength >= $currentLength + $animatorOffset
                                || !$points
                            ) {
                                $perc
                                = ($currentLength + $animatorOffset - $segmentLength)
                                / ($currentPoint['partialLength'] ?? 0);
                                $xPathPos
                                = (int) ($prevPoint['point'][0] ?? 0)
                                + ((int) ($currentPoint['point'][0] ?? 0) - (int) ($prevPoint['point'][0] ?? 0))
                                * $perc;
                                $yPathPos
                                = (int) ($prevPoint['point'][1] ?? 0)
                                + ((int) ($currentPoint['point'][1] ?? 0) - (int) ($prevPoint['point'][1] ?? 0))
                                * $perc;
                                $matrixHelper->translate(
                                    -$alignment[0] * ($letters[$i]['an'] ?? 0) * 0.005,
                                    -($alignment[1] * $yOff) * 0.01
                                );
                                $shouldMeasure = false;
                            } elseif (count($points) > 0) {
                                $segmentLength += (int) $currentPoint['partialLength'];
                                ++$pointInd;
                                if ($pointInd >= count($points)) {
                                    $pointInd = 0;
                                    ++$segmentInd;
                                    if ($segments[$segmentInd]) {
                                        $points = $segments[$segmentInd]['points'] ?? [];
                                    } elseif ($mask->v['c'] ?? null) {
                                        $pointInd = 0;
                                        $segmentInd = 0;
                                        $points = $segments[$segmentInd]['points'] ?? [];
                                    } else {
                                        $segmentLength -= (int) $currentPoint['partialLength'];
                                        $points = null;
                                    }
                                }
                                if ($points) {
                                    $prevPoint = $currentPoint;
                                    $currentPoint = $points[$pointInd];
                                    $partialLength = $currentPoint['partialLength'] ?? 0;
                                }
                            }
                        }
                        $offf = ($letters[$i]['an'] ?? 0) / 2 - ($letters[$i]['add'] ?? 0);
                        $matrixHelper->translate(
                            -$offf,
                            0,
                            0
                        );
                    } else {
                        $offf = ($letters[$i]['an'] ?? 0) / 2 - ($letters[$i]['add'] ?? 0);
                        $matrixHelper->translate(
                            -$offf,
                            0,
                            0
                        );

                        // Grouping alignment
                        $matrixHelper->translate(
                            -$alignment[0] * ($letters[$i]['an'] ?? 0) * 0.005,
                            -$alignment[1] * $yOff * 0.01,
                            0
                        );
                    }

                    for ($j = 0; $j < $jLen; ++$j) {
                        $animatorProps = $animators[$j]['a'] ?? [];
                        if (!$animatorProps['t']['propType'] ?? null) {
                            continue;
                        }
                        $animatorSelector = $animators[$j]['s'] ?? null;
                        $mult = $animatorSelector?->getMult(
                            (int) ($letters[$i]['anIndexes'][$j] ?? 0),
                            $textData['a'][$j]['s']['totalChars'] ?? 0
                        );
                        // This condition is to prevent applying tracking to first character in each line. Might be better to use a boolean "isNewLine"
                        if (0 !== $xPos || 0 !== $documentData['j']) {
                            if ($this->_hasMaskedPath) {
                                if (is_array($mult)) {
                                    $currentLength += (int) $animatorProps['t']->v * ($mult[0] ?? 1);
                                } else {
                                    $currentLength += (int) $animatorProps['t']->v * (int) $mult;
                                }

                                continue;
                            }

                            if (is_array($mult)) {
                                $xPos += (int) $animatorProps['t']->v * ($mult[0] ?? 1);

                                continue;
                            }
                            $xPos += (int) $animatorProps['t']->v * (int) $mult;
                        }
                    }
                    if ($documentData['strokeWidthAnim']) {
                        $sw = $documentData['sw'] ?? 0;
                    }
                    if ($documentData['strokeColorAnim']) {
                        if ($documentData['sc']) {
                            $sc = [
                                $documentData['sc'][0],
                                $documentData['sc'][1],
                                $documentData['sc'][2],
                            ];
                        } else {
                            $sc = [
                                0,
                                0,
                                0,
                            ];
                        }
                    }
                    if ($documentData['fillColorAnim'] && $documentData['fc']) {
                        $fc = [
                            (int) $documentData['fc'][0],
                            (int) $documentData['fc'][1],
                            (int) $documentData['fc'][2],
                        ];
                    }
                    for ($j = 0; $j < $jLen; ++$j) {
                        $animatorProps = $animators[$j]['a'] ?? [];
                        if ($animatorProps['a']['propType'] ?? null) {
                            $animatorSelector = $animators[$j]['s'] ?? null;
                            $mult = $animatorSelector?->getMult(
                                $letters[$i]['anIndexes'][$j] ?? 0,
                                $textData['a'][$j]['s']['totalChars'] ?? 0
                            );
                            if (!$mult || !is_array($animatorProps['a']->v)) {
                                continue;
                            }

                            if (is_array($mult)) {
                                $matrixHelper->translate(
                                    -($animatorProps['a']->v[0] ?? 0) * ($mult[0] ?? 1),
                                    -($animatorProps['a']->v[1] ?? 0) * ($mult[1] ?? 1),
                                    ($animatorProps['a']->v[2] ?? 0) * ($mult[2] ?? 1)
                                );

                                continue;
                            }
                            $matrixHelper->translate(
                                -($animatorProps['a']->v[0] ?? 0) * $mult,
                                -($animatorProps['a']->v[1] ?? 0) * $mult,
                                ($animatorProps['a']->v[2] ?? 0) * $mult
                            );
                        }
                    }
                    for ($j = 0; $j < $jLen; ++$j) {
                        $animatorProps = $animators[$j]['a'] ?? [];
                        if ($animatorProps['s']['propType'] ?? null) {
                            $animatorSelector = $animators[$j]['s'] ?? null;
                            $mult = $animatorSelector?->getMult(
                                $letters[$i]['anIndexes'][$j] ?? 0,
                                $textData['a'][$j]['s']['totalChars'] ?? 0
                            );
                            if (!$mult || !is_array($animatorProps['s']->v)) {
                                continue;
                            }
                            if (is_array($mult)) {
                                $matrixHelper->scale(
                                    1 + (($animatorProps['s']->v[0] ?? 0) - 1) * ($mult[0] ?? 1),
                                    1 + (($animatorProps['s']->v[1] ?? 0) - 1) * ($mult[1] ?? 1),
                                    1
                                );

                                continue;
                            }
                            $matrixHelper->scale(
                                1 + (($animatorProps['s']->v[0] ?? 0) - 1) * $mult,
                                1 + (($animatorProps['s']->v[1] ?? 0) - 1) * $mult,
                                1
                            );
                        }
                    }

                    for ($j = 0; $j < $jLen; ++$j) {
                        $animatorProps = $animators[$j]['a'] ?? [];
                        $animatorSelector = $animators[$j]['s'] ?? null;
                        $mult = $animatorSelector?->getMult(
                            $letters[$i]['anIndexes'][$j] ?? 0,
                            $textData['a'][$j]['s']['totalChars'] ?? null
                        );
                        if (!$mult) {
                            continue;
                        }
                        if ($animatorProps['sk']['propType']) {
                            if (is_array($mult)) {
                                $matrixHelper->skewFromAxis(
                                    -(int) $animatorProps['sk']->v * ($mult[0] ?? 1),
                                    (int) ($animatorProps['sa']->v ?? 0) * ($mult[1] ?? 1)
                                );
                            } else {
                                $matrixHelper->skewFromAxis(
                                    -(int) $animatorProps['sk']->v * $mult,
                                    (int) ($animatorProps['sa']->v ?? 0) * $mult
                                );
                            }
                        }
                        if ($animatorProps['r']['propType'] ?? null) {
                            if (is_array($mult)) {
                                $matrixHelper->rotateZ(-(int) $animatorProps['r']->v * ($mult[2] ?? 1));
                            } else {
                                $matrixHelper->rotateZ(-(int) $animatorProps['r']->v * $mult);
                            }
                        }
                        if ($animatorProps['ry']['propType'] ?? null) {
                            if (is_array($mult)) {
                                $matrixHelper->rotateY((int) $animatorProps['ry']->v * ($mult[1] ?? 1));
                            } else {
                                $matrixHelper->rotateY((int) $animatorProps['ry']->v * $mult);
                            }
                        }
                        if ($animatorProps['rx']['propType'] ?? null) {
                            if (is_array($mult)) {
                                $matrixHelper->rotateX((int) $animatorProps['rx']->v * ($mult[0] ?? 1));
                            } else {
                                $matrixHelper->rotateX((int) $animatorProps['rx']->v * $mult);
                            }
                        }
                        if ($animatorProps['o']['propType'] ?? null) {
                            if (is_array($mult)) {
                                $elemOpacity
                                += ((int) $animatorProps['o']->v * ($mult[0] ?? 1) - $elemOpacity) * ($mult[0] ?? 1);
                            } else {
                                $elemOpacity
                                += ((int) $animatorProps['o']->v * $mult - $elemOpacity) * $mult;
                            }
                        }
                        if ($documentData['strokeWidthAnim'] && ($animatorProps['sw']['propType'] ?? null)) {
                            if (is_array($mult)) {
                                $sw += (int) $animatorProps['sw']->v * ($mult[0] ?? 1);
                            } else {
                                $sw += (int) $animatorProps['sw']->v * $mult;
                            }
                        }
                        if (
                            $documentData['strokeColorAnim']
                            && ($animatorProps['sc']['propType'] ?? null)
                            && is_array($animatorProps['sc']->v)
                        ) {
                            for ($k = 0; $k < 3; ++$k) {
                                if (is_array($mult)) {
                                    $sc[$k] += (($animatorProps['sc']->v[$k] ?? 0) - ($sc[$k] ?? 0)) * ($mult[0] ?? 1);
                                } else {
                                    $sc[$k] += (($animatorProps['sc']->v[$k] ?? 0) - ($sc[$k] ?? 0)) * $mult;
                                }
                            }
                        }
                        if ($documentData['fillColorAnim'] && $documentData['fc']) {
                            if ($animatorProps['fc']['propType'] ?? null) {
                                for ($k = 0; $k < 3; ++$k) {
                                    if (!is_array($animatorProps['fc']->v)) {
                                        continue;
                                    }
                                    if (is_array($mult)) {
                                        $fc[$k] += (($animatorProps['fc']->v[$k] ?? 0) - ($fc[$k] ?? 0)) * ($mult[0] ?? 1);
                                    } else {
                                        $fc[$k] += (($animatorProps['fc']->v[$k] ?? 0) - ($fc[$k] ?? 0)) * $mult;
                                    }
                                }
                            }
                            if ($animatorProps['fh']['propType'] ?? null) {
                                if (is_array($mult)) {
                                    $fc = self::addHueToRGB($fc, (int) $animatorProps['fh']->v * ($mult[0] ?? 1));
                                } else {
                                    $fc = self::addHueToRGB($fc, (int) $animatorProps['fh']->v * $mult);
                                }
                            }
                            if ($animatorProps['fs']['propType'] ?? null) {
                                if (is_array($mult)) {
                                    $fc = self::addSaturationToRGB(
                                        $fc,
                                        (int) $animatorProps['fs']->v * ($mult[0] ?? 1)
                                    );
                                } else {
                                    $fc = self::addSaturationToRGB($fc, (int) $animatorProps['fs']->v * $mult);
                                }
                            }
                            if ($animatorProps['fb']['propType'] ?? null) {
                                if (is_array($mult)) {
                                    $fc = self::addBrightnessToRGB(
                                        $fc,
                                        (int) $animatorProps['fb']->v * ($mult[0] ?? 1)
                                    );
                                } else {
                                    $fc = self::addBrightnessToRGB($fc, (int) $animatorProps['fb']->v * $mult);
                                }
                            }
                        }
                    }

                    for ($j = 0; $j < $jLen; ++$j) {
                        $animatorProps = $animators[$j]['a'] ?? [];

                        if ($animatorProps['p']['propType'] ?? null) {
                            $animatorSelector = $animators[$j]['s'] ?? null;
                            $mult = $animatorSelector?->getMult(
                                $letters[$i]['anIndexes'][$j] ?? 0,
                                $textData['a'][$j]['s']['totalChars'] ?? 0
                            );
                            if (!$mult || !is_array($animatorProps['p']->v)) {
                                continue;
                            }
                            if ($this->_hasMaskedPath) {
                                if (is_array($mult)) {
                                    $matrixHelper->translate(
                                        0,
                                        $animatorProps['p']->v[1] * $mult[0],
                                        -$animatorProps['p']->v[2] * $mult[1]
                                    );
                                } else {
                                    $matrixHelper->translate(
                                        0,
                                        $animatorProps['p']->v[1] * $mult,
                                        -$animatorProps['p']->v[2] * $mult
                                    );
                                }
                            } elseif (is_array($mult)) {
                                $matrixHelper->translate(
                                    $animatorProps['p']->v[0] * $mult[0],
                                    $animatorProps['p']->v[1] * $mult[1],
                                    -$animatorProps['p']->v[2] * $mult[2]
                                );
                            } else {
                                $matrixHelper->translate(
                                    $animatorProps['p']->v[0] * $mult,
                                    $animatorProps['p']->v[1] * $mult,
                                    -$animatorProps['p']->v[2] * $mult
                                );
                            }
                        }
                    }
                    if ($documentData['strokeWidthAnim']) {
                        $letterSw = $sw < 0 ? 0 : $sw;
                    }
                    if ($documentData['strokeColorAnim']) {
                        $letterSc = toRGBString($sc);
                    }
                    if ($documentData['fillColorAnim'] && $documentData['fc']) {
                        $letterFc = toRGBString($fc);
                    }

                    if ($this->_hasMaskedPath) {
                        $matrixHelper->translate(0, -(int) $documentData['ls']);

                        $matrixHelper->translate(
                            0,
                            $alignment[1] * $yOff * 0.01 + $yPos,
                            0
                        );
                        if ($this->_pathData['p']->v ?? null) {
                            $tanAngle
                            = ((int) ($currentPoint['point'][1] ?? 0) - (int) ($prevPoint['point'][1] ?? 0))
                            / ((int) ($currentPoint['point'][0] ?? 0) - (int) ($prevPoint['point'][0] ?? 0));
                            $rot = atan($tanAngle) * 180 / M_PI;

                            if ((int) ($currentPoint['point'][0] ?? 0) < (int) ($prevPoint['point'][0] ?? 0)) {
                                $rot += 180;
                            }
                            $matrixHelper->rotate(-$rot * M_PI / 180);
                        }
                        $matrixHelper->translate(
                            $xPathPos,
                            $yPathPos,
                            0
                        );
                        $currentLength -= $alignment[0] * ($letters[$i]['an'] ?? 0) * 0.005;
                        if ($letters[$i + 1] && $ind !== ($letters[$i + 1]['ind'] ?? 0)) {
                            $currentLength += ($letters[$i]['an'] ?? 0) / 2;
                            $currentLength
                              += $documentData['tr'] * 0.001 * (int) $documentData['finalSize'];
                        }
                    } else {
                        $matrixHelper->translate(
                            $xPos,
                            $yPos,
                            0
                        );

                        if ($documentData['ps']) {
                            $matrixHelper->translate(
                                $documentData['ps'][0],
                                $documentData['ps'][1] + (int) $documentData['ascent'],
                                0
                            );
                        }

                        switch ($documentData['j']) {
                            case 1:
                                $matrixHelper->translate(
                                    ($letters[$i]['animatorJustifyOffset'] ?? 0)
                  + (int) $documentData['justifyOffset']
                  + ((int) $documentData['boxWidth']
                                    - (int) $documentData['lineWidths'][$letters[$i]['line'] ?? 0]),
                                    0,
                                    0
                                );

                                break;

                            case 2:
                                $matrixHelper->translate(
                                    ($letters[$i]['animatorJustifyOffset'] ?? 0)
                  + (int) $documentData['justifyOffset']
                  + ((int) $documentData['boxWidth']
                                    - (int) $documentData['lineWidths'][$letters[$i]['line'] ?? 0])
                                    / 2,
                                    0,
                                    0
                                );

                                break;

                            case null:
                            default:
                                break;
                        }
                        $matrixHelper->translate(0, -(int) $documentData['ls']);
                        $matrixHelper->translate(
                            $offf,
                            0,
                            0
                        );
                        $matrixHelper->translate(
                            $alignment[0] * (int) $letters[$i]['an'] * 0.005,
                            $alignment[1] * $yOff * 0.01,
                            0
                        );
                        $xPos
                        += ($letters[$i]['l'] ?? 0)
                        + $documentData['tr'] * 0.001 * (int) $documentData['finalSize'];
                    }
                    if (RendererType::HTML === $renderType) {
                        $letterM = $matrixHelper->toCSS();
                    } elseif (RendererType::SVG === $renderType) {
                        $letterM = $matrixHelper->to2dCSS();
                    } else {
                        $letterP = [
                            $matrixHelper->props[0] ?? 0,
                            $matrixHelper->props[1] ?? 0,
                            $matrixHelper->props[2] ?? 0,
                            $matrixHelper->props[3] ?? 0,
                            $matrixHelper->props[4] ?? 0,
                            $matrixHelper->props[5] ?? 0,
                            $matrixHelper->props[6] ?? 0,
                            $matrixHelper->props[7] ?? 0,
                            $matrixHelper->props[8] ?? 0,
                            $matrixHelper->props[9] ?? 0,
                            $matrixHelper->props[10] ?? 0,
                            $matrixHelper->props[11] ?? 0,
                            $matrixHelper->props[12] ?? 0,
                            $matrixHelper->props[13] ?? 0,
                            $matrixHelper->props[14] ?? 0,
                            $matrixHelper->props[15] ?? 0,
                        ];
                    }
                    $letterO = $elemOpacity;
                }

                if ($renderedLettersCount <= $i) {
                    $letterValue = new LetterProps(
                        (int) $letterO,
                        (int) $letterSw,
                        $letterSc,
                        $letterFc,
                        $letterM,
                        $letterP
                    );
                    array_push($this->renderedLetters, $letterValue);
                    ++$renderedLettersCount;
                    $this->{$lettersChangedFlag} = true;

                    continue;
                }

                $letterValue = $this->renderedLetters[$i]['Props'];
                $this->{$lettersChangedFlag}
                = $letterValue->update(
                    (int) $letterO,
                    (int) $letterSw,
                    $letterSc,
                    $letterFc,
                    $letterM, // matrix()-string
                    $letterP
                ) || $this->{$lettersChangedFlag};
            }
        } catch (\Exception $e) {
            error_log(get_class($this).' => '.$e->getMessage());
        }
    }

    public function getValue($_flag)
    {
        if ($this->_elem['globalData']['frameId'] ?? null === $this->_frameId) {
            return 0;
        }
        $this->_frameId = $this->_elem['globalData']['frameId'] ?? 0;
        $this->iterateDynamicProperties();

        return 0;
    }

    public function searchProperties(array $_)
    {
        $length = count($this->_textData['a'] ?? []);

        for ($i = 0; $i < $length; ++$i) {
            $this->_animatorsData[$i] = new TextAnimatorDataProperty(
                $this->_elem,
                $this->_textData['a'][$i] ?? null,
                $this
            );
        }

        if ($this->_textData['p'] && array_key_exists('m', $this->_textData['p']) && $this->_elem['maskManager']) {
            $this->_pathData = [
                'a' => PropertyFactory::getProp(
                    $this->_elem,
                    $this->_textData['p']['a'],
                    0,
                    0,
                    $this
                ),
                'f' => PropertyFactory::getProp(
                    $this->_elem,
                    $this->_textData['p']['f'],
                    0,
                    0,
                    $this
                ),
                'l' => PropertyFactory::getProp(
                    $this->_elem,
                    $this->_textData['p']['l'],
                    0,
                    0,
                    $this
                ),
                'm' => $this->_elem['maskManager']->getMaskProperty($this->_textData['p']['m']),
                'p' => PropertyFactory::getProp(
                    $this->_elem,
                    $this->_textData['p']['p'],
                    0,
                    0,
                    $this
                ),
                'r' => PropertyFactory::getProp(
                    $this->_elem,
                    $this->_textData['p']['r'],
                    0,
                    0,
                    $this
                ),
            ];
            $this->_hasMaskedPath = true;
        } else {
            $this->_hasMaskedPath = false;
        }
        $this->_moreOptions['alignment'] = PropertyFactory::getProp(
            $this->_elem,
            $this->_textData['m']['a'] ?? null,
            1,
            0,
            $this
        );
    }
    private array $_animatorsData;
    private BaseElement|BaseProperty $_elem;
    private $_hasMaskedPath = false;
    private $_moreOptions = ['alignment' => []];
    private $_pathData = [];
    private RendererType $_renderType;
    private array $_textData;
    private Matrix $mHelper;
}
