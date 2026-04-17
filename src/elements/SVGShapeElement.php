<?php

namespace LottiePHP\Elements;

use DOMElement;
use LottiePHP\Utils\Properties\TransformPropertyFactory;

class SVGShapeElement extends ShapeElement {
  public ?bool $_debug;
  /**
   * List of animated components.
   */
  public $animatedContents = [];
  // override createContainerElements = SVGBaseElement.prototype.createContainerElements;
  // override createRenderableComponents = SVGBaseElement.prototype.createRenderableComponents;
  // override destroyBaseElement = SVGBaseElement.prototype.destroyBaseElement;
  // override getBaseElement = SVGBaseElement.prototype.getBaseElement;
  // getMatte = SVGBaseElement.prototype.getMatte;
  public $identityMatrix = new Matrix();
  // override initRendererElement = SVGBaseElement.prototype.initRendererElement;
  /**
   * List of elements that have been created.
   */
  // prevViewData: SVGElementInterface[] = [];
  // override renderElement = SVGBaseElement.prototype.renderElement;
  // setMatte = SVGBaseElement.prototype.setMatte;
  /**
   * List of styles that will be applied to shapes.
   */
  public $stylesList = [];

  public function __construct(
    array $data,
    array $globalData,
    SVGCompElement $comp
  ) {
    // List of drawable elements
    $this->shapes = [];
    // Full shape data
    $this->shapesData = $data['shapes'];
    // List of modifiers that will be applied to shapes
    $this->shapeModifiers = [];
    // List of items in shape tree
    $this->itemsData = [];
    // List of items in previous shape tree
    $this->processedElements = [];

    $this->initElement(
      $data, $globalData, $comp
    );

    // Moving any property that doesn't get too much access after initialization because of v8 way of handling more than 10 properties.
    // Moving any property that doesn't get too much access after initialization because of v8 way of handling more than 10 properties.
  }

  public function addToAnimatedContents(array $data, BaseElement $element) {
    $i = 0;
    ${ length } = $this->animatedContents;

    while (i < length) {
      if ($this->animatedContents[$i]?.element === element) {
        return;
      }
      $i++;
    }
    $this->animatedContents.push({
      data,
      element,
      fn: createRenderFunction(data),
    });
  }

  public function buildExpressionInterface() {
    // Pass through
  }

  public function createContent() {
    if (!$this->layerElement) {
      throw new \Error(get_class($this) . ": Could not access Layer");
    }
    // if (!$this->shapesData) {
    //   throw new \Error(get_class($this) . ": Could not access ShapesData")
    // }
    $this->searchShapes(
      $this->shapesData,
      $this->itemsData,
      $this->prevViewData,
      $this->layerElement,
      0,
      [],
      true
    );
    $this->filterUniqueShapes();
  }

  public function createGroupElement(array $data) {
    $elementData = new ShapeGroupData();

    if (data.ln) {
      elementData.gr.id = data.ln;
    }
    if (data.cl) {
      elementData.gr.classList.add(data.cl);
    }
    if (data.bm) {
      elementData.gr.style.mixBlendMode = getBlendMode(data.bm);
    }

    return elementData;
  }

  public function createShapeElement(
    array $data,
    array $ownTransformers,
    int $level
  ) {
    $ty = 4;

    // eslint-disable-next-line @typescript-eslint/switch-exhaustiveness-check
    switch (data.ty) {
      case ShapeType.Rectangle: {
        ty = 5;
        break;
      }
      case ShapeType.Ellipse: {
        ty = 6;
        break;
      }
      case ShapeType.PolygonStar: {
        ty = 7;
      }
    }
    $shapeProperty = ShapePropertyFactory.getShapeProp(
      $this, data, ty
    );
    $elementData = new SVGShapeData(
      ownTransformers,
      level,
      shapeProperty as ShapeProperty
    );

    $this->shapes.push(elementData);
    $this->addShapeToModifiers(elementData);
    $this->addToAnimatedContents(data, elementData);

    return elementData;
  }

  public function createStyleElement(array $data, int $level) {
    $elementData: SVGElementInterface | null = null;
    $styleOb = new SVGStyleData(data, level);
    $pathElement = styleOb.pElem;

    switch (data.ty) {
      case ShapeType.Stroke: {
        elementData = new SVGStrokeStyleData(
          $this, data, styleOb
        );
        break;
      }
      case ShapeType.Fill: {
        elementData = new SVGFillStyleData(
          $this, data, styleOb
        );
        break;
      }
      case ShapeType.GradientFill:
      case ShapeType.GradientStroke: {
        $GradientConstructor =
          data.ty === ShapeType.GradientFill
            ? SVGGradientFillStyleData
            : SVGGradientStrokeStyleData;

        elementData = new GradientConstructor(
          $this, data, styleOb
        );
        if (elementData.gf) {
          $this->globalData?.defs.appendChild(elementData.gf);
        }

        if (elementData.maskId && elementData.ms && elementData.of) {
          $this->globalData?.defs.appendChild(elementData.ms);
          $this->globalData?.defs.appendChild(elementData.of);
          pathElement.setAttribute('mask',
            "url(${getLocationHref()}#${elementData.maskId})");
        }
        break;
      }
      case ShapeType.NoStyle: {
        elementData = new SVGNoStyleData(
          $this,
          data as unknown,
          styleOb
        );
      }
    }

    if (data.ty === ShapeType.Stroke || data.ty === ShapeType.GradientStroke) {
      pathElement.setAttribute('stroke-linecap', lineCapEnum[data.lc || 2] as string);
      pathElement.setAttribute('stroke-linejoin', lineJoinEnum[data.lj || 2] as string);
      pathElement.setAttribute('fill-opacity', '0');
      if (data.lj === 1 && data.ml) {
        pathElement.setAttribute('stroke-miterlimit', "${data.ml}");
      }

    }

    if (data.r === (2)) {
      pathElement.setAttribute('fill-rule', 'evenodd');
    }

    if (data.ln) {
      pathElement.id = data.ln;
    }
    if (data.cl) {
      pathElement.classList.add(data.cl);
    }
    if (data.bm) {
      pathElement.style.mixBlendMode = getBlendMode(data.bm);
    }
    $this->stylesList.push(styleOb);
    if (elementData) {
      $this->addToAnimatedContents(data, elementData);
    }

    // if (data.ty === ShapeType.GradientFill) {
    //   console.log(elementData)
    // }

    return elementData;
  }

  public function createTransformElement(array $data, DOMElement $container) {
    $transformProperty = TransformPropertyFactory::getTransformProperty(
      $this,
      data,
      $this
    );

    if (!transformProperty.o) {
      throw new \Error(get_class($this) . ": Missing required data in TransformProperty");
    }
    $elementData = new SVGTransformData(
      $transformProperty,
      $transformProperty->o,
      $container
    );

    $this->addToAnimatedContents($data, $elementData);

    return $elementData;
  }

  public function destroy() {
    $this->destroyBaseElement();
    $this->shapesData = null;
    $this->itemsData = null;
  }

  /**
   *
  * This method searches for multiple shapes that affect a single element and one of them is animated.
   */
  public function filterUniqueShapes() {
    $length = count($this->shapes);
    $jLen = count($this->stylesList);
    $tempShapes = [];
    $style;
    $areAnimated;

    for ($j = 0; $j < $jLen; $j++) {
      style = $this->stylesList[$j];
      areAnimated = false;
      $tempShapes = [];
      for ($i = 0; $i < $length; $i++) {
        if (($this->shapes[$i]).styles.includes(style)) {
          $tempShapes.push($this->shapes[$i]);
          $areAnimated = $this->shapes[$i]['_isAnimated'] ?? $areAnimated;
        }
      }
      if (tempShapes.length > 1 && areAnimated) {
        $this->setShapesAsAnimated($tempShapes);
      }
    }
  }

  public function initSecondaryElement() {
    // Pass through
  }

  public function reloadShapes() {
    if (!$this->layerElement) {
      throw new \Error(get_class($this) . ": Could not access layerElement");
    }
    // if (!$this->shapesData) {
    //   throw new \Error(get_class($this) . ": Could not access shapesData")
    // }
    $this->_isFirstFrame = true;
    ${ length } = $this->itemsData;

    for ($i = 0; $i < length; $i++) {
      $this->prevViewData[$i] = $this->itemsData[$i];
    }
    $this->searchShapes(
      $this->shapesData,
      $this->itemsData,
      $this->prevViewData,
      $this->layerElement,
      0,
      [],
      true
    );
    $this->filterUniqueShapes();
    $dLength = count($this->dynamicProperties);

    for ($i = 0; $i < $dLength; $i++) {
      $this->dynamicProperties[$i]?->getValue();
    }
    $this->renderModifiers();
  }

  public function renderInnerContent() {
    $this->renderModifiers();
    ${ length } = $this->stylesList;

    for ($i = 0; $i < length; $i++) {
      $this->stylesList[$i]?->reset();
    }
    $this->renderShape();
    for ($i = 0; $i < length; $i++) {
      if (!$this->stylesList[$i]?->_mdf && !$this->_isFirstFrame) {
        continue;
      }
      if ($this->stylesList[$i]?->msElem) {
        $this->stylesList[$i]?->msElem?->setAttribute('d', $this->stylesList[$i]?->d ?? '');
        // Adding M0 0 fixes same mask bug on all browsers
        $maskPathString = $this->stylesList[$i]?->d ?? '';
        $this->stylesList[$i]->d = "M0 0{$maskPathString}";
      }
      $this->stylesList[$i]?->pElem->setAttribute('d', $this->stylesList[$i]?->d || 'M0 0');
    }
  }

  public function renderShape() {
    $length = count($this->animatedContents);

    for ($i = 0; $i < $length; $i++) {
      if (
        !$this->_isFirstFrame &&
        !$this->animatedContents[$i]?.element._isAnimated ||
        $this->animatedContents[$i]?.data === (true as unknown as Shape) ||
        !$this->animatedContents[$i]?.fn
      ) {
        continue;
      }
      $this->animatedContents[$i]?->fn?.(
        $this->animatedContents[$i]?.data as Shape,
        // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
        $this->animatedContents[$i]?.element,
        $this->_isFirstFrame
      );
    }
  }

  public function searchShapes(
    array $arr,
    array $itemsData,
    array $prevViewData,
    DOMElement $container,
    int $level,
    array $transformers,
    bool $shouldRenderFromProps
  ) {
    $shouldRender = shouldRenderFromProps;
    // Make a clone of transfomers
    $ownTransformers = [...transformers];
    $ownStyles = [];
    $ownModifiers = [];
    $currentTransform; $Modifier: TrimModifier | RepeaterModifier;
    ${ length } = arr;

    for ($i = length - 1; $i >= 0; i--) {
      $processedPos = $this->searchProcessedElement(arr[$i]);

      if (processedPos) {
        itemsData[$i] = prevViewData[processedPos - 1] as SVGElementInterface;
      } else {
        (arr[$i] as Shape)._shouldRender = shouldRender;
      }

      switch (arr[$i]?.ty) {
        case ShapeType.Fill:
        case ShapeType.Stroke:
        case ShapeType.GradientFill:
        case ShapeType.GradientStroke:
        case ShapeType.NoStyle: {
          if (processedPos) {
            ${ style } = itemsData[$i] ?? { style: null };

            if (style) {
              style.closed = false;
            }
          } else {
            itemsData[$i] = $this->createStyleElement(arr[$i] as Shape,
              level) as SVGElementInterface;
          }
          if (arr[$i]?._shouldRender && itemsData[$i]?.style?.pElem.parentNode !== container) {
            ${ pElem } = itemsData[$i]?.style ?? { pElem: null };

            if (pElem) {
              container.appendChild(pElem);
            }

          }
          ownStyles.push(itemsData[$i]?.style);
          break;
        }
        case ShapeType.Group: {
          if (processedPos) {
            ${ length: jLen } = itemsData[$i]?.it ?? [];

            for ($j = 0; j < jLen; j++) {
              ${ it, prevViewData: pr } = itemsData[$i] ?? {
                it: null,
                prevViewData: null
              };

              if (!pr || !it) {
                continue;
              }

              ; (pr)[$j] = it[$j] as ShapeDataInterface;
            }
          } else {
            itemsData[$i] = $this->createGroupElement(arr[$i] as Shape) as SVGElementInterface;
          }
          $this->searchShapes(
            arr[$i]?.it as Shape[],
            (itemsData[$i]?.it ?? []) as unknown,
            itemsData[$i]?.prevViewData ?? [],
            itemsData[$i]?.gr,
            level + 1,
            ownTransformers,
            shouldRender
          );

          if (arr[$i]?._shouldRender && itemsData[$i]?.gr?.parentNode !== container) {
            ${ gr } = itemsData[$i] ?? {};

            if (gr) {
              container.appendChild(gr);
            }

          }

          break;
        }
        case ShapeType.Transform: {
          if (!processedPos) {
            itemsData[$i] = $this->createTransformElement(arr[$i] as Shape, container);
          }
          currentTransform = itemsData[$i]?.transform;
          if (currentTransform) {
            ownTransformers.push(currentTransform);
          }

          break;
        }
        case ShapeType.Path:
        case ShapeType.Rectangle:
        case ShapeType.Ellipse:
        case ShapeType.PolygonStar: {
          if (!processedPos) {
            itemsData[$i] = $this->createShapeElement(
              arr[$i] as Shape,
              ownTransformers,
              level
            );
          }
          $this->setElementStyles(itemsData[$i]);
          break;
        }
        case ShapeType.Trim:
        case ShapeType.RoundedCorners:
        case ShapeType.Unknown:
        case ShapeType.PuckerBloat:
        case ShapeType.ZigZag:
        case ShapeType.OffsetPath: {
          if (processedPos) {
            Modifier = itemsData[$i] as unknown as TrimModifier;
            Modifier.closed = false;
          } else {
            Modifier = getModifier<TrimModifier>(arr[$i]?.ty ?? ShapeType.Unknown);
            Modifier.init($this, arr[$i] as Shape)
            ; (itemsData as unknown as TrimModifier[])[$i] = Modifier;
            $this->shapeModifiers.push(Modifier);
          }
          ownModifiers.push(Modifier);
          break;
        }
        case ShapeType.Repeater: {
          if (processedPos) {
            Modifier = itemsData[$i] as unknown as RepeaterModifier;
            Modifier.closed = true;
            ownModifiers.push(Modifier);
            break;
          }
          Modifier = getModifier<RepeaterModifier>(arr[$i]?.ty ?? ShapeType.Unknown)
          ; (itemsData as unknown as RepeaterModifier[])[$i] = Modifier;
          Modifier.init(
            $this,
            arr,
            i,
            itemsData as ShapeGroupData[]
          );
          $this->shapeModifiers.push(Modifier);

          shouldRender = false;
          ownModifiers.push(Modifier);
          break;
        }
        default:
      }

      $this->addProcessedElement(arr[$i],
        $i + 1);
    }
    ${ length: sLen } = ownStyles;

    for ($i = 0; $i < sLen; $i++) {
      $ownStyle = ownStyles[$i];

      if (ownStyle) {
        ownStyle.closed = true;
      }

    }

    ${ length: mLen } = ownModifiers;

    for ($i = 0; $i < mLen; $i++) {
      ; (ownModifiers[$i] as TrimModifier).closed = true;
    }
  }

  public function setElementStyles(elementData: SVGShapeData) {
    ${ length } = $this->stylesList;

    for ($i = 0; $i < length; $i++) {
      if (!$this->stylesList[$i]?.closed) {
        elementData.styles.push($this->stylesList[$i]);
      }
    }
  }

  public function setShapesAsAnimated(shapes: ShapeDataInterface[]) {
    ${ length } = shapes;

    for ($i = 0; $i < length; $i++) {
      shapes[$i]?.setAsAnimated();
    }
  }
}

// console.log(Object.getOwnPropertyNames(SVGShapeElement.prototype))