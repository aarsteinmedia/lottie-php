<?php

namespace LottiePHP\Elements;

use LottiePHP\Utils\LocationHref;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\PropertyFactory;
use LottiePHP\Utils\Shapes\Properties\ShapePropertyFactory;
use LottiePHP\Utils\Shapes\ShapePath;

use function LottiePHP\Utils\buildShapeString;
use function LottiePHP\Utils\createElementID;
use function LottiePHP\Utils\createNS;
use function LottiePHP\Utils\createSizedArray;

class MaskElement
{
    public ?array $data;
    public ?BaseElement $element;
    public ?array $globalData;
    public ?\DOMElement $maskElement = null;
    public ?array $masksProperties = [];
    public string $solidPath = '';
    public array $storedData = [];
    public array $viewData;

    public function __construct(
        array $data,
        BaseElement $element,
        array $globalData,
    ) {
        $this->data = $data;
        $this->element = $element;
        $this->globalData = $globalData;
        $this->masksProperties = $this->data['masksProperties'] ?? [];

        /** @var \DOMElement $defs */
        ['defs' => $defs] = $this->globalData;

        $length = count($this->masksProperties);

        $this->viewData = createSizedArray($length);

        $properties = $this->masksProperties;
        $count = 0;
        $currentMasks = [];
        $layerId = createElementID();

        /** @var ?\DOMElement $rect */
        $rect = null;

        /** @var ?\DOMElement $feMorph */
        $feMorph = null;
        $x = null;
        $maskType = 'clipPath';
        $maskRef = 'clip-path';

        for ($i = 0; $i < $length; ++$i) {
            if (
                ($properties[$i]['mode'] ?? '') !== 'a'
                && ($properties[$i]['mode'] ?? '') !== 'n'
                || ($properties[$i]['inv'] ?? false)
                || ($properties[$i]['o']['k'] ?? 0) !== 100
                || ($properties[$i]['o']['x'] ?? null)
            ) {
                $maskType = 'mask';
                $maskRef = 'mask';
            }

            if (
                (($properties[$i]['mode'] ?? '') === 's'
                || ($properties[$i]['mode'] ?? '') === 'i')
                && 0 === $count
            ) {
                $rect = createNS('rect');
                $rect->setAttribute('fill', '#ffffff');
                $rect->setAttribute('width', $this->element['comp']['data']['w'] ?? 0);
                $rect->setAttribute('height', $this->element['comp']['data']['h'] ?? 0);
                array_push($currentMasks, $rect);
            } else {
                $rect = null;
            }

            $path = createNS('path');

            if (($properties[$i]['mode'] ?? '') === 'n') {
                $this->viewData[$i] = [
                    'elem' => $path,
                    'lastPath' => '',
                    'op' => PropertyFactory::getProp(
                        $this->element,
                        $properties[$i]['o'] ?? null,
                        0,
                        0.01,
                        $this->element
                    ),
                    'prop' => ShapePropertyFactory::getShapeProp(
                        $this->element,
                        $properties[$i],
                        3
                    ),
                ];
                $defs->appendChild($path);

                continue;
            }
            ++$count;

            $path->setAttribute(
                'fill',
                's' === $properties[$i]['mode'] ? '#000000' : '#ffffff'
            );
            $path->setAttribute('clip-rule', 'nonzero');

            $filterID = null;

            if (($properties[$i]['x']['k'] ?? null) === 0) {
                $feMorph = null;
                $x = null;
            } else {
                $maskType = 'mask';
                $maskRef = 'mask';
                $x = PropertyFactory::getProp(
                    $this->element,
                    $properties[$i]['x'],
                    0,
                    null,
                    $this->element
                );
                $filterID = createElementID();
                $expansor = createNS('filter');

                $expansor->setAttribute('id', $filterID);
                $feMorph = createNS('feMorphology');
                $feMorph->setAttribute('operator', 'erode');
                $feMorph->setAttribute('in', 'SourceGraphic');
                $feMorph->setAttribute('radius', '0');
                $expansor->appendChild($feMorph);
                $defs->appendChild($expansor);
                $path->setAttribute(
                    'stroke',
                    ($properties[$i]['mode'] ?? '') === 's' ? '#000000' : '#ffffff'
                );
            }

            // TODO: move $this to a factory or to a constructor
            $this->storedData[$i] = [
                'elem' => $path,
                'expan' => $feMorph,
                'filterId' => $filterID,
                'lastOperator' => '',
                'lastPath' => '',
                'lastRadius' => 0,
                'x' => $x,
            ];
            if (($properties[$i]['mode'] ?? '') === 'i') {
                $jLen = count($currentMasks);
                $g = createNS('g');

                for ($j = 0; $j < $jLen; ++$j) {
                    $g->appendChild($currentMasks[$j]);
                }
                $mask = createNS('mask');

                $mask->setAttribute('mask-type', 'alpha');
                $mask->setAttribute('id', "{$layerId}_{$count}");
                $mask->appendChild($path);
                $defs->appendChild($mask);
                $locationHref = LocationHref::getHref();
                $g->setAttribute('mask', "url({$locationHref}#{$layerId}_{$count})");

                $currentMasks = [];
                array_push($currentMasks, $g);
            } else {
                array_push($currentMasks, $path);
            }
            if ($properties[$i]['inv'] && !$this->solidPath) {
                $this->solidPath = $this->createLayerSolidPath();
            }
            // TODO: move $this to a factory or to a constructor
            $this->viewData[$i] = [
                'elem' => $path,
                'invRect' => $rect,
                'lastPath' => '',
                'op' => PropertyFactory::getProp(
                    $this->element,
                    $properties[$i]['o'] ?? null,
                    0,
                    0.01,
                    $this->element
                ),
                'prop' => ShapePropertyFactory::getShapeProp(
                    $this->element,
                    $properties[$i],
                    3
                ),
            ];
            $shapePath = $this->viewData[$i]['prop']->v ?? null;

            if (!$this->viewData[$i]['prop']['k'] ?? null && $shapePath) {
                $this->drawPath(
                    $properties[$i] ?? null,
                    $shapePath,
                    $this->viewData[$i]
                );
            }
        }

        $this->maskElement = createNS($maskType);

        $cLen = count($currentMasks);

        for ($i = 0; $i < $cLen; ++$i) {
            $this->maskElement->appendChild($currentMasks[$i]);
        }

        if ($count > 0) {
            $this->maskElement->setAttribute('id', $layerId);
            $locationHref = LocationHref::getHref();
            $this->element['maskedElement']?->setAttribute(
                $maskRef,
                "url({$locationHref}#{$layerId})"
            );
            $defs->appendChild($this->maskElement);
        }
        if (count($this->viewData) > 0) {
            $this->element->addRenderableComponent($this);
        }
    }

    public function createLayerSolidPath()
    {
        $path = 'M0,0 ';

        $w = $this->globalData['compSize']['w'] ?? 0;
        $h = $this->globalData['compSize']['h'] ?? 0;

        $path .= " h{$w}";
        $path .= " v{$h}";
        $path .= " h-{$w}";
        $path .= " v-{$h} ";

        return $path;
    }

    public function destroy()
    {
        $this->element = null;
        $this->globalData = null;
        $this->maskElement = null;
        $this->data = null;
        $this->masksProperties = null;
    }

    public function drawPath(
        ?array $pathData,
        ShapePath $pathNodes,
        array $viewData
    ) {
        // Reuse the same path-string builder as regular SVG shapes,
        // but with an identity matrix (masks are in local coordinates).
        static $identityMat = null;
        if (null === $identityMat) {
            $identityMat = new Matrix();
        }
        $len = $pathNodes->_length ?? 0;
        $pathString = buildShapeString($pathNodes, $len, (bool) $pathNodes->c, $identityMat);

        if ($viewData['lastPath'] !== $pathString) {
            $pathShapeValue = '';

            if ($pathNodes->c) {
                $pathShapeValue = ($pathData['inv'] ?? false)
                    ? $this->solidPath.$pathString
                    : $pathString;
            }
            $viewData['elem']->setAttribute('d', $pathShapeValue);
            $viewData['lastPath'] = $pathString;
        }
    }

    public function getMaskelement()
    {
        return $this->maskElement;
    }

    public function getMaskProperty(int $pos)
    {
        return $this->viewData[$pos]['prop'] ?? null;
    }

    public function renderFrame(?int $frame)
    {
        $finalMat = $this->element->finalTransform['mat'] ?? null;
        $length = count($this->masksProperties);

        for ($i = 0; $i < $length; ++$i) {
            $shapePath = $this->viewData[$i]['prop']->v ?? null;

            if ($shapePath && ($this->viewData[$i]['prop']['_mdf'] ?? $frame)) {
                $this->drawPath(
                    $this->masksProperties[$i] ?? null,
                    $shapePath,
                    $this->viewData[$i]
                );
            }
            if ($this->viewData[$i]['op']['_mdf'] ?? $frame) {
                ($this->viewData[$i]['elem'] ?? null)?->setAttribute(
                    'fill-opacity',
                    $this->viewData[$i]['op']->v ?? 1
                );
            }
            if (($this->masksProperties[$i]['mode'] ?? '') === 'n') {
                continue;
            }
            if (
                ($this->viewData[$i]['invRect'] ?? null)
                && ($this->element->finalTransform['mProp']['_mdf'] ?? $frame)
            ) {
                ($this->viewData[$i]['invRect'] ?? null)?->setAttribute(
                    'transform',
                    $finalMat->getInverseMatrix()->to2dCSS()
                );
            }
            $storedData = $this->storedData[$i];

            if (
                !($storedData['x'] ?? null)
                || !($storedData['x']['_mdf'] ?? $frame)
            ) {
                continue;
            }
            ['expan' => $feMorph] = $storedData;

            if ($storedData['x']->v < 0) {
                if ('erode' !== $storedData['lastOperator']) {
                    $storedData['lastOperator'] = 'erode';
                    $locationHref = LocationHref::getHref();
                    ['filterId' => $filterId] = $storedData;
                    $storedData['elem']->setAttribute(
                        'filter',
                        "url({$locationHref}\\#{$filterId})"
                    );
                }
                $feMorph?->setAttribute('radius', -$storedData['x']->v);

                continue;
            }
            if ('dilate' !== $storedData['lastOperator']) {
                $storedData['lastOperator'] = 'dilate';
                $storedData['elem']->removeAttribute('filter');
            }
            $storedData['elem']->setAttribute(
                'stroke-width',
                $storedData['x']->v * 2
            );
        }
    }
}
