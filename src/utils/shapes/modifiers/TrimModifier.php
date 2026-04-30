<?php

namespace LottiePHP\Utils\Shapes\Modifiers;

use LottiePHP\Elements\BaseElement;
use LottiePHP\Utils\Bezier;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\Pooling\ShapePool;
use LottiePHP\Utils\Properties\KeyframedValueProperty;
use LottiePHP\Utils\Properties\ValueProperty;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\ShapeCollection;
use LottiePHP\Utils\Shapes\ShapePath;

use function LottiePHP\Utils\Pooling\segmentsLengthPool;

class TrimModifier extends ShapeModifier
{
    public KeyframedValueProperty|ValueProperty|null $e;

    public ?float $eValue;

    public $m;
    public KeyframedValueProperty|ValueProperty|null $o;

    public KeyframedValueProperty|ValueProperty|null $s;

    public ?float $sValue;

    public function addPaths(array $newPaths, ShapeCollection $localShapeCollection)
    {
        $length = count($newPaths);

        for ($i = 0; $i < $length; ++$i) {
            $localShapeCollection->addShape($newPaths[$i]);
        }
    }

    public function addSegment(
        array $pt1,
        array $pt2,
        array $pt3,
        array $pt4,
        ShapePath $shapePath,
        int $pos,
        ?bool $isNewShape
    ) {
        $shapePath->setXYAt(
            $pt2[0],
            $pt2[1],
            'o',
            $pos
        );
        $shapePath->setXYAt(
            $pt3[0],
            $pt3[1],
            'i',
            $pos + 1
        );
        if ($isNewShape) {
            $shapePath->setXYAt(
                $pt1[0],
                $pt1[1],
                'v',
                $pos
            );
        }
        $shapePath->setXYAt(
            $pt4[0],
            $pt4[1],
            'v',
            $pos + 1
        );
    }

    public function addSegmentFromArray(
        array $points,
        ShapePath $shapePath,
        int $pos,
        ?bool $isNewShape
    ) {
        $shapePath->setXYAt(
            $points[1] ?? 0,
            $points[5] ?? 0,
            'o',
            $pos
        );
        $shapePath->setXYAt(
            $points[2] ?? 0,
            $points[6] ?? 0,
            'i',
            $pos + 1
        );
        if ($isNewShape) {
            $shapePath->setXYAt(
                $points[0] ?? 0,
                $points[4] ?? 0,
                'v',
                $pos
            );
        }
        $shapePath->setXYAt(
            $points[3] ?? 0,
            $points[7] ?? 0,
            'v',
            $pos + 1
        );
    }

    public function addShapes(
        object $shapeData,
        array $shapeSegment,
        ?ShapePath $shapePathFromProps = null
    ) {
        $shapePath = $shapePathFromProps;
        $pathsData = $shapeData->pathsData ?? [];
        $shape = $shapeData->shape ?? null;
        $shapePaths = $shape?->paths?->shapes ?? [];
        $len = $shape?->paths?->_length ?? 0;
        $j = 0;
        $jLen = 0;
        $addedLength = 0;
        $currentLengthData = null;
        $segmentCount = 0;
        $segment = null;
        $shapes = [];
        $initPos = 0;
        $isNewShape = true;

        if ($shapePath) {
            $segmentCount = $shapePath->_length;
            $initPos = $shapePath->_length;
        } else {
            $shapePath = ShapePool::newElement();
            $segmentCount = 0;
            $initPos = 0;
        }
        array_push($shapes, $shapePath);

        for ($i = 0; $i < $len; ++$i) {
            // if (!$shapePath) {
            //   continue
            // }
            ['lengths' => $lengths] = $pathsData[$i] ?? ['lengths' => []];

            $shapePath->c = (bool) $shapePaths[$i]?->c;
            $jLen = $shapePaths[$i]?->c ? count($lengths) : count($lengths) + 1;
            for ($j = 1; $j < $jLen; ++$j) {
                $currentLengthData = $lengths[$j - 1];
                if ($addedLength + $currentLengthData->addedLength < $shapeSegment['s']) {
                    $addedLength += $currentLengthData->addedLength;
                    $shapePath->c = false;

                    continue;
                }
                if ($addedLength > $shapeSegment['e']) {
                    $shapePath->c = false;

                    break;
                }
                if (
                    $shapeSegment['s'] <= $addedLength
                    && $shapeSegment['e'] >= $addedLength + $currentLengthData->addedLength
                ) {
                    $this->addSegment(
                        $shapePaths[$i]?->v[$j - 1],
                        $shapePaths[$i]?->o[$j - 1],
                        $shapePaths[$i]?->i[$j],
                        $shapePaths[$i]?->v[$j],
                        $shapePath,
                        $segmentCount,
                        $isNewShape
                    );
                    $isNewShape = false;
                } else {
                    $segment = Bezier::getNewSegment(
                        $shapePaths[$i]?->v[$j - 1],
                        $shapePaths[$i]?->v[$j],
                        $shapePaths[$i]?->o[$j - 1],
                        $shapePaths[$i]?->i[$j],
                        ($shapeSegment['s'] - $addedLength) / $currentLengthData->addedLength,
                        ($shapeSegment['e'] - $addedLength) / $currentLengthData->addedLength,
                        $lengths[$j - 1]
                    );
                    $this->addSegmentFromArray(
                        $segment,
                        $shapePath,
                        $segmentCount,
                        $isNewShape
                    );
                    $isNewShape = false;
                    $shapePath->c = false;
                }
                $addedLength += $currentLengthData->addedLength;
                ++$segmentCount;
            }
            if ($shapePaths[$i]?->c && count($lengths) > 0) {
                $currentLengthData = $lengths[$j - 1];
                if ($addedLength <= $shapeSegment['e']) {
                    $segmentLength = $lengths[$j - 1]?->addedLength ?? 0;

                    if (
                        $shapeSegment['s'] <= $addedLength
                        && $shapeSegment['e'] >= $addedLength + $segmentLength
                    ) {
                        $this->addSegment(
                            $shapePaths[$i]?->v[$j - 1],
                            $shapePaths[$i]?->o[$j - 1],
                            $shapePaths[$i]?->i[0],
                            $shapePaths[$i]?->v[0],
                            $shapePath,
                            $segmentCount,
                            $isNewShape
                        );
                        $isNewShape = false;
                    } else {
                        $segment = Bezier::getNewSegment(
                            $shapePaths[$i]?->v[$j - 1],
                            $shapePaths[$i]?->v[0],
                            $shapePaths[$i]?->o[$j - 1],
                            $shapePaths[$i]?->i[0],
                            ($shapeSegment['s'] - $addedLength) / $segmentLength,
                            ($shapeSegment['e'] - $addedLength) / $segmentLength,
                            $lengths[$j - 1]
                        );
                        $this->addSegmentFromArray(
                            $segment,
                            $shapePath,
                            $segmentCount,
                            $isNewShape
                        );
                        $isNewShape = false;
                        $shapePath->c = false;
                    }
                } else {
                    $shapePath->c = false;
                }
                $addedLength += $currentLengthData?->addedLength ?? 0;
                ++$segmentCount;
            }
            if ($shapePath->_length) {
                $shapePath->setXYAt(
                    $shapePath->v[$initPos][0] ?? 0,
                    $shapePath->v[$initPos][1] ?? 0,
                    'i',
                    $initPos
                );
                $shapePath->setXYAt(
                    $shapePath->v[$shapePath->_length - 1][0] ?? 0,
                    $shapePath->v[$shapePath->_length - 1][1] ?? 0,
                    'o',
                    $shapePath->_length - 1
                );
            }
            if ($addedLength > $shapeSegment['e']) {
                break;
            }
            if ($i < $len - 1) {
                $shapePath = ShapePool::newElement();
                $isNewShape = true;

                array_push($shapes, $shapePath);

                $segmentCount = 0;
            }
        }

        return $shapes;
    }

    public function addShapeToModifier(array|object $shapeData)
    {
        if (is_array($shapeData)) {
            $shapeData['pathsData'] = [];

            return;
        }
        $shapeData->pathsData = [];
    }

    public function calculateShapeEdges(
        int $s,
        int $e,
        int $shapeLength,
        int $addedLength,
        int $totalModifierLength
    ) {
        $segments = [];

        if ($e <= 1) {
            array_push($segments, ['e' => $e, 's' => $s]);
        } elseif ($s >= 1) {
            array_push($segments, ['e' => $e - 1, 's' => $s - 1]);
        } else {
            array_push($segments, ['e' => 1, 's' => $s], ['e' => $e - 1, 's' => 0]);
        }
        $shapeSegments = [];
        $length = count($segments);

        for ($i = 0; $i < $length; ++$i) {
            if (
                !(
                    ($segments[$i]?->e ?? 0) * $totalModifierLength < $addedLength
          || ($segments[$i]?->s ?? 0) * $totalModifierLength > $addedLength + $shapeLength
                )
            ) {
                if ($segments[$i]?->s ?? 0 * $totalModifierLength <= $addedLength) {
                    $shapeS = 0;
                } else {
                    $shapeS
                      = ($segments[$i]?->s ?? 0 * $totalModifierLength - $addedLength) / $shapeLength;
                }
                if ($segments[$i]?->e ?? 0 * $totalModifierLength >= $addedLength + $shapeLength) {
                    $shapeE = 1;
                } else {
                    $shapeE
                      = ($segments[$i]?->e ?? 0 * $totalModifierLength - $addedLength) / $shapeLength;
                }
                array_push($shapeSegments, [$shapeS, $shapeE]);
            }
        }
        if (0 === count($shapeSegments)) {
            array_push($shapeSegments, [0, 0]);
        }

        return $shapeSegments;
    }

    public function getValue(...$args)
    {
        return $this->processKeys(...$args);
    }

    public function initModifierProperties(
        BaseElement|DynamicPropertyContainer $elem,
        array $data
    ) {
        $this->s = PropertyFactory::getProp(
            $elem,
            $data['s'],
            0,
            0.01,
            $this
        );
        $this->e = PropertyFactory::getProp(
            $elem,
            $data['e'],
            0,
            0.01,
            $this
        );
        $this->o = PropertyFactory::getProp(
            $elem,
            $data['o'],
            0,
            0,
            $this
        );
        $this->sValue = 0.0;
        $this->eValue = 0.0;
        $this->m = $data['m'];
        $this->_isAnimated
          = count($this->s->effectsSequence) > 0
          || count($this->e->effectsSequence) > 0
         || count($this->o->effectsSequence) > 0;
    }

    public function processShapes(bool $_isFirstFrame)
    {
        $s = null;
        $e = 0;

        if ($this->_mdf || $_isFirstFrame) {
            $o = fmod((float) $this->o->v, 360.0) / 360.0;

            if ($o < 0) {
                ++$o;
            }
            if ((float) $this->s?->v > 1) {
                $s = 1 + $o;
            } elseif ((float) $this->s?->v < 0) {
                $s = 0 + $o;
            } else {
                $s = (float) $this->s?->v + $o;
            }
            if ((float) $this->e?->v > 1) {
                $e = 1 + $o;
            } elseif ((float) $this->e?->v < 0) {
                $e = 0 + $o;
            } else {
                $e = (float) $this->e?->v + $o;
            }

            if ($s > $e) {
                $_s = $s;

                $s = $e;
                $e = $_s;
            }
            $s = round($s * 10000) * 0.0001;
            $e = round($e * 10000) * 0.0001;
            $this->sValue = $s;
            $this->eValue = $e;
        } else {
            $s = $this->sValue ?? 0.0;
            $e = $this->eValue ?? 0.0;
        }

        // Fast path: full trim range means no trim should be applied.
        // Use an epsilon because these are floats (avoid `===` issues).
        //
        // Important: when an offset (`o`) is applied, `s`/`e` get shifted and may no longer be (0,1),
        // even though the span still covers the full path. Detect that by span ~= 1.
        $eps = 1e-6;
        $span = (float) $e - (float) $s;
        $isFullSpan = abs($span - 1.0) < $eps;
        $isFullForward = abs($s - 0.0) < $eps && abs($e - 1.0) < $eps;
        $isFullBackward = abs($s - 1.0) < $eps && abs($e - 0.0) < $eps;
        if ($isFullSpan || $isFullForward || $isFullBackward) {
            $length = count($this->shapes);
            for ($i = 0; $i < $length; ++$i) {
                $shape = $this->shapes[$i]->shape ?? null;
                if ($shape) {
                    $shape->_mdf = true;
                    // Restore the original (untrimmed) paths collection.
                    $shape->paths = $shape->localShapeCollection ?? $shape->paths;
                }
                if ($this->_mdf) {
                    $this->shapes[$i]->pathsData = [];
                }
            }

            return;
        }
        $shapePaths = null;
        $j = 0;
        $jLen = 0;
        $pathsData = null;
        $pathData = null;
        $totalShapeLength = 0;
        $totalModifierLength = 0;

        $length = count($this->shapes);

        if (abs((float) $e - (float) $s) < $eps) {
            for ($i = 0; $i < $length; ++$i) {
                $this->shapes[$i]?->localShapeCollection?->releaseShapes();

                $shape = $this->shapes[$i]->shape;

                if ($shape) {
                    $shape->_mdf = true;
                    $shape->paths = $this->shapes[$i]?->localShapeCollection;
                }

                if ($this->_mdf) {
                    $this->shapes[$i]->pathsData = [];
                }
            }
        } else {
            $segments = [];
            $shapeData = null;
            $localShapeCollection = null;

            for ($i = 0; $i < $length; ++$i) {
                $shapeData = $this->shapes[$i];
                // if shape hasn't changed and trim properties haven't changed, cached previous path can be used
                if (
                    !$shapeData->shape?->_mdf
                    && !$this->_mdf
                    && !$_isFirstFrame
                    && 2 !== $this->m
                ) {
                    if ($shapeData->shape) {
                        $shapeData->shape->paths = $shapeData->localShapeCollection;
                    }
                } else {
                    $shapePaths = $shapeData->shape?->paths;
                    $jLen = $shapePaths?->_length ?? 0;
                    $totalShapeLength = 0;
                    if (!$shapeData->shape?->_mdf && count($shapeData->pathsData ?? [])) {
                        $totalShapeLength = $shapeData->totalShapeLength;
                    } else {
                        $pathsData = $this->releasePathsData($shapeData->pathsData);
                        for ($j = 0; $j < $jLen; ++$j) {
                            if (!$shapePaths) {
                                continue;
                            }
                            $pathData = Bezier::getSegmentsLength($shapePaths->shapes[$j]);
                            $pathsData[] = $pathData;
                            $totalShapeLength += $pathData['totalLength'];
                        }
                        $shapeData->totalShapeLength = $totalShapeLength;
                        $shapeData->pathsData = $pathsData;
                    }

                    $totalModifierLength += (float) $totalShapeLength;

                    if ($shapeData->shape) {
                        $shapeData->shape->_mdf = true;
                    }
                }
            }
            $shapeS = $s;
            $shapeE = $e;
            $addedLength = 0;
            $edges = null;

            for ($i = $length - 1; $i >= 0; --$i) {
                $shapeData = $this->shapes[$i];
                if ($shapeData->shape?->_mdf) {
                    $localShapeCollection = $shapeData->localShapeCollection;
                    if (!$localShapeCollection) {
                        throw new \Error(get_class($this).": Could not set {$localShapeCollection}");
                    }
                    $localShapeCollection->releaseShapes();
                    if (2 === $this->m && $length > 1) {
                        $edges = $this->calculateShapeEdges(
                            $s,
                            $e,
                            $shapeData->totalShapeLength ?? 0,
                            $addedLength,
                            $totalModifierLength
                        );
                        $addedLength += (float) $shapeData->totalShapeLength;
                    } else {
                        $edges = [[$shapeS, $shapeE]];
                    }
                    $jLen = count($edges);
                    for ($j = 0; $j < $jLen; ++$j) {
                        $shapeS = $edges[$j][0] ?? 0;
                        $shapeE = $edges[$j][1] ?? 0;
                        $segments = [];
                        if ($shapeE <= 1) {
                            array_push($segments, [
                                'e' => (float) $shapeData->totalShapeLength * $shapeE,
                                's' => (float) $shapeData->totalShapeLength * $shapeS,
                            ]);
                        } elseif ($shapeS >= 1) {
                            array_push($segments, [
                                'e' => (float) $shapeData->totalShapeLength * ($shapeE - 1),
                                's' => (float) $shapeData->totalShapeLength * ($shapeS - 1),
                            ]);
                        } else {
                            array_push(
                                $segments,
                                [
                                    'e' => (float) $shapeData->totalShapeLength,
                                    's' => (float) $shapeData->totalShapeLength * $shapeS,
                                ],
                                [
                                    'e' => (float) $shapeData->totalShapeLength * ($shapeE - 1),
                                    's' => 0,
                                ]
                            );
                        }

                        $emptySegment = [
                            'e' => 0,
                            's' => 0,
                        ];

                        $newShapesData = $this->addShapes($shapeData, $segments[0] ?? $emptySegment);

                        if (($segments[0]['s'] ?? 0) !== ($segments[0]['e'] ?? 0)) {
                            if (count($segments) > 1) {
                                $lastShapeInCollection
                                  = $shapeData->shape->paths?->shapes[
                                    $shapeData->shape->paths->_length - 1
                                  ];

                                if ($lastShapeInCollection?->c) {
                                    $lastShape = array_pop($newShapesData);

                                    $this->addPaths($newShapesData, $localShapeCollection);
                                    $newShapesData = $this->addShapes(
                                        $shapeData,
                                        $segments[1] ?? $emptySegment,
                                        $lastShape
                                    );
                                } else {
                                    $this->addPaths($newShapesData, $localShapeCollection);
                                    $newShapesData = $this->addShapes($shapeData, $segments[1] ?? $emptySegment);
                                }
                            }
                            $this->addPaths($newShapesData, $localShapeCollection);
                        }
                    }
                    $shapeData->shape->paths = $localShapeCollection;
                }
            }
        }
    }

    public function releasePathsData(array $pathsData)
    {
        $length = count($pathsData);

        for ($i = 0; $i < $length; ++$i) {
            segmentsLengthPool()->release($pathsData[$i]);
        }

        return [];
    }
}
