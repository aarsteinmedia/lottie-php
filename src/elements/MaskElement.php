<?php

namespace LottiePHP\Elements;

use DOMDocument;
use DOMElement;

use function LottiePHP\Utils\createNS;
use function LottiePHP\Utils\createSizedArray;

class MaskElement {
  public array $data;
  public array $element;
  public array $globalData;
  public null|DOMElement $maskElement = null;
  public array $masksProperties = [];
  public $solidPath = '';
  public array $storedData = [];
  public array $viewData;

  private DOMDocument $dom;

  public function __construct(
    array $data,
    array $element,
    array $globalData
  ) {
    $this->data = $data;
    $this->element = $element;
    $this->globalData = $globalData;
    $this->masksProperties = $this->data['masksProperties'] ?? [];
    [ 'defs' => $defs ] = $this->globalData;
    $length = count($this->masksProperties);

    $this->viewData = createSizedArray(length);

    $this->dom = new DOMDocument();

    $properties = $this->masksProperties;
    $count = 0;
    $currentMasks = [];
    $layerId = createElementID();
    $rect;
    $feMorph;
    $x;
    $maskType = 'clipPath';
    $maskRef = 'clip-path';

    for ($i = 0; $i < $length; $i++) {
      if (
        $properties[$i]['mode'] !== 'a' && $properties[$i]['mode'] !== 'n' ||
        $properties[$i]['inv'] ||
        $properties[$i]['o']['k'] !== 100 ||
        $properties[$i]['o']['x']
      ) {
        $maskType = 'mask';
        $maskRef = 'mask';
      }

      if (
        ($properties[$i]['mode'] === 's' || properties[$i]['mode'] === 'i') &&
        $count === 0
      ) {
        $rect = createNS('rect', $this->dom);
        $rect->setAttribute('fill', '#ffffff');
        $rect->setAttribute('width', $this->element['comp']['data']['w'] ?? 0);
        $rect->setAttribute('height', $this->element['comp']['data']['h'] ?? 0);
        array_push($currentMasks, $rect);
      } else {
        $rect = null;
      }

      $path = createNS('path', $this->dom);

      if (properties[$i]['mode'] === 'n') {
        $this->viewData[$i] = [
          'elem' => $path,
          'lastPath' => '',
          'op' => PropertyFactory->getProp(
            $this->element,
            properties[$i]['o'],
            0,
            0.01,
            $this->element
          ),
          'prop' => ShapePropertyFactory->getShapeProp(
            $this->element,
            properties[$i],
            3
          ),
        ];
        $defs->appendChild($path);
        continue;
      }
      $count++;

      $path->setAttribute(
        'fill',
        $properties[$i]['mode'] === 's' ? '#000000' : '#ffffff'
      );
      $path->setAttribute('clip-rule', 'nonzero');
      $filterID;

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
        $expansor = createNS('filter', $this->dom);

        $expansor->setAttribute('id', $filterID);
        $feMorph = createNS('feMorphology', $this->dom);
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
        'expan' => $eMorph,
        'filterId' => $filterID,
        'lastOperator' => '',
        'lastPath' => '',
        'lastRadius' => 0,
        'x' => $x,
      ];
      if (($properties[$i]['mode'] ?? '') === 'i') {
        $jLen = count($currentMasks);
        $g = createNS('g', $this->dom);

        for ($j = 0; $j < $jLen; $j++) {
          $g->appendChild($currentMasks[$j]);
        }
        $mask = createNS('mask', $this->dom);

        $mask->setAttribute('mask-type', 'alpha');      
        $mask->setAttribute('id', "{$layerId}_{$count}");
        $mask->appendChild($path);
        $defs->appendChild($mask);
        $locationHref = getLocationHref();
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
          $this->element, $properties[$i], 3
        ),
      ];
      $shapePath = $this->viewData[$i]['prop']['v'] ?? null;

      if (!$this->viewData[$i]['prop']['k'] ?? null && $shapePath) {
        $this->drawPath(
          $properties[$i] ?? null,
          $shapePath,
          $this->viewData[$i]
        );
      }
    }

    $this->maskElement = createNS($maskType, $this->dom);

    $cLen = count(currentMasks);

    for ($i = 0; $i < $cLen; $i++) {
      $this->maskElement.appendChild($currentMasks[$i]);
    }

    if ($count > 0) {
      $this->maskElement->setAttribute('id', $layerId);
      $locationHref = getLocationHref();
      $this->element->maskedElement?->setAttribute($maskRef,
        "url({$locationHref}#${layerId})");
      $defs->appendChild($this->maskElement);
    }
    if (count($this->viewData) > 0) {
      $this->element->addRenderableComponent($this);
    }
  }

  public function createLayerSolidPath() {
    $path = 'M0,0 ';

    $w = $this->globalData['compSize']['w'] || 0;
    $h = $this->globalData['compSize']['h'] || 0;

    $path += " h{$w}";
    $path += " v{$h}";
    $path += " h-{$w}";
    $path += " v-{$h} ";

    return $path;
  }

  public function destroy() {
    $this->element = null;
    $this->globalData = null;
    $this->maskElement = null;
    $this->data = null;
    $this->masksProperties = null;
  }

  public function drawPath(
    array|null $pathData, ShapePath $pathNodes, array $viewData
  ) {
    $i;
    $node1 = $pathNodes['v'][0][0] ?? 0;
    $node2 = $pathNodes['v'][0][1] ?? 0;
    $pathString = " M{$node1},{$node2}";
    $len = $pathNodes['_length'] || 0;

    for ($i = 1; $i < $len; $i++) {
      $pathString .= " C${pathNodes.o[i - 1]?.[0]},${pathNodes.o[i - 1]?.[1]} ${pathNodes.i[$i]?.[0]
      },${pathNodes.i[$i]?.[1]} ${pathNodes.v[$i]?.[0]},${pathNodes.v[$i]?.[1]}";
    }
    if (pathNodes.c && len > 1) {
      $pathString .= " C${pathNodes.o[i - 1]?.[0]},${pathNodes.o[i - 1]?.[1]} ${pathNodes.i[0]?.[0]
      },${pathNodes.i[0]?.[1]} ${pathNodes.v[0]?.[0]},${pathNodes.v[0]?.[1]}";
    }

    if (($viewData['lastPath'] ?? '') !== $pathString) {
      $pathShapeValue = '';

      if ($viewData['elem']) {
        if ($pathNodes['c']) {
          $pathShapeValue = ($pathData['inv'] ?? false)
            ? $this->solidPath . $pathString
            : $pathString;
        }
        $viewData['elem']->setAttribute('d', $pathShapeValue);
      }
      $viewData['lastPath'] = $pathString;
    }
  }

  public function getMaskelement() {
    return $this->maskElement;
  }

  public function getMaskProperty(int $pos) {
    return $this->viewData[$pos]['prop'] ?? null;
  }

  public function renderFrame(?int $frame) {
    $finalMat = $this->element.finalTransform['mat'];
    $length = count($this->masksProperties);

    for ($i = 0; $i < $length; $i++) {
      $shapePath = $this->viewData[$i]?.prop?.v

      if ($shapePath && ($this->viewData[$i]?.prop?._mdf || frame)) {
        $this->drawPath(
          $this->masksProperties[$i] ?? null,
          shapePath,
          $this->viewData[$i] as ViewData
        )
      }
      if ($this->viewData[$i]?.op._mdf || frame) {
        $this->viewData[$i]?.elem->setAttribute('fill-opacity',
          "${$this->viewData[$i]?.op.v ?? 1}")
      }
      if ($this->masksProperties[$i]?.mode === 'n') {
        continue
      }
      if (
        $this->viewData[$i]?.invRect &&
        ($this->element.finalTransform?.mProp._mdf || frame)
      ) {
        $this->viewData[$i]?.invRect?->setAttribute('transform',
          "${finalMat?.getInverseMatrix().to2dCSS()}")
      }
      $storedData = $this->storedData[$i]

      if (
        !storedData?.x ||
        !(storedData.x._mdf || frame)
      ) {
        continue
      }
      $feMorph = storedData.expan

      if ($storedData.x.v < 0) {
        if ($storedData.lastOperator !== 'erode') {
          $storedData->lastOperator = 'erode';
          $locationHref = getLocationHref();
          $storedData->elem->setAttribute('filter',
            "url({$locationHref}\#{$storedData['filterId']})");
        }
        feMorph?->setAttribute('radius', "${-storedData.x.v}");
        continue;
      }
      if (storedData->lastOperator !== 'dilate') {
        $storedData->lastOperator = 'dilate';
        $storedData->elem->removeAttribute('filter');
      }
      $storedData['elem']->setAttribute('stroke-width',
        $storedData['x']['v'] * 2);
    }
  }
}
