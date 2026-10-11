<?php

namespace LottiePHP\Elements;

use LottiePHP\Elements\Helpers\RenderableElement;
use LottiePHP\Elements\Helpers\Shapes\ShapeGroupData;
use LottiePHP\Elements\Helpers\Shapes\SVGElementsRenderer;
use LottiePHP\Elements\Helpers\Shapes\SVGFillStyleData;
use LottiePHP\Elements\Helpers\Shapes\SVGGradientFillStyleData;
use LottiePHP\Elements\Helpers\Shapes\SVGGradientStrokeStyleData;
use LottiePHP\Elements\Helpers\Shapes\SVGNoStyleData;
use LottiePHP\Elements\Helpers\Shapes\SVGShapeData;
use LottiePHP\Elements\Helpers\Shapes\SVGStrokeStyleData;
use LottiePHP\Elements\Helpers\Shapes\SVGStyleData;
use LottiePHP\Elements\Helpers\Shapes\SVGTransformData;
use LottiePHP\Utils\Enums\ShapeType;
use LottiePHP\Utils\Helpers\DynamicPropertyContainer;
use LottiePHP\Utils\LocationHref;
use LottiePHP\Utils\Matrix;
use LottiePHP\Utils\Properties\TransformPropertyFactory;
use LottiePHP\Utils\Shapes\Modifiers\ShapeModifier;
use LottiePHP\Utils\Shapes\Modifiers\ShapeModifiers;
use LottiePHP\Utils\Shapes\Properties\ShapePropertyFactory;

use function LottiePHP\Utils\addClasses;
use function LottiePHP\Utils\addStyles;
use function LottiePHP\Utils\Enums\lineCapEnum;
use function LottiePHP\Utils\Enums\lineJoinEnum;
use function LottiePHP\Utils\getBlendMode;

class SVGShapeElement extends ShapeElement
{
    use SVGBaseTraits;

    public ?bool $_debug;

    /**
     * List of animated components.
     */
    public $animatedContents = [];
    public Matrix $identityMatrix;

    /**
     * List of elements that have been created.
     */
    public $prevViewData = [];

    /**
     * List of styles that will be applied to shapes.
     */
    public $stylesList = [];

    private function debugLog(string $message): void
    {
        if (!getenv('LOTTIE_PHP_DEBUG')) {
            return;
        }
        error_log('[LottiePHP][SVGShapeElement] '.$message);
    }

    private function rebindShapeModifiers(): void
    {
        if (!$this->_reloadingShapes) {
            return;
        }
        foreach ($this->shapeModifiers as $modifier) {
            $modifier->shapes = [];
            $modifier->closed = false;
        }
        foreach ($this->shapes as $shapeData) {
            if ($shapeData instanceof SVGShapeData) {
                $this->addShapeToModifiers($shapeData);
            }
        }
    }

    private function registerShapeModifier(ShapeModifier $modifier, bool $isNew): void
    {
        if ($isNew || !in_array($modifier, $this->shapeModifiers, true)) {
            array_push($this->shapeModifiers, $modifier);
        }
    }

    public function __construct(
        array $data,
        array $globalData,
        BaseElement|RenderableElement|SVGCompElement $comp
    ) {
        $this->identityMatrix = new Matrix();

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
            $data,
            $globalData,
            $comp
        );
    }

    public function addToAnimatedContents(
        array|SVGShapeData $data,
        DynamicPropertyContainer|SVGShapeData|SVGShapeElement|SVGTransformData $element
    ) {
        $i = 0;
        $length = count($this->animatedContents);

        while ($i < $length) {
            if (($this->animatedContents[$i]['element'] ?? null) === $element) {
                return;
            }
            ++$i;
        }
        array_push(
            $this->animatedContents,
            [
                'data' => $data,
                'element' => $element,
                'fn' => SVGElementsRenderer::createRenderFunction($data),
            ]
        );
    }

    public function createContent()
    {
        if (!$this->layerElement) {
            throw new \Error(get_class($this).': Could not access Layer');
        }

        $this->searchShapes(
            $this->shapesData,
            $this->itemsData,
            $this->prevViewData,
            $this->layerElement,
            0,
            [],
            true,
            []
        );
        $this->filterUniqueShapes();

        $this->debugLog(
            'createContent done: shapesData='
            .count($this->shapesData ?? [])
            .' shapes='.count($this->shapes ?? [])
            .' stylesList='.count($this->stylesList ?? [])
            .' animatedContents='.count($this->animatedContents ?? [])
            .' shapesWithStyles='
            .count(array_filter($this->shapes ?? [], fn ($s) => count($s?->styles ?? []) > 0))
        );
    }

    public function createGroupElement(array $data)
    {
        $elementData = new ShapeGroupData();

        if (!empty($data['ln'] ?? null)) {
            $elementData->gr->setAttribute('id', $data['ln']);
        }
        if (!empty($data['cl'] ?? null)) {
            addClasses($elementData->gr, [$data['cl']]);
        }
        if (!empty($data['bm'] ?? null)) {
            addStyles($elementData->gr, ['mix-blend-mode' => getBlendMode((int) $data['bm'])]);
        }

        // Keep the original TS-like shape tree structure expected by `searchShapes`.
        return [
            'gr' => $elementData->gr,
            'it' => [],
            'prevViewData' => [],
        ];
    }

    public function createShapeElement(
        array $data,
        array $ownTransformers,
        int $level
    ) {
        $ty = 4;

        $shapeTy = ShapeType::tryFrom($data['ty'] ?? '');

        switch ($shapeTy) {
            case ShapeType::Rectangle:
                $ty = 5;

                break;

            case ShapeType::Ellipse:
                $ty = 6;

                break;

            case ShapeType::PolygonStar:
                $ty = 7;
        }
        $shapeProperty = ShapePropertyFactory::getShapeProp(
            $this,
            $data,
            $ty
        );
        $elementData = new SVGShapeData(
            $ownTransformers,
            $level,
            $shapeProperty
        );

        array_push($this->shapes, $elementData);
        $this->addShapeToModifiers($elementData);
        $this->addToAnimatedContents($data, $elementData);

        return $elementData;
    }

    public function createStyleElement(array $data, int $level)
    {
        $elementData = null;
        $styleOb = new SVGStyleData($data, $level);
        $pathElement = $styleOb->pElem;

        $styleTy = ShapeType::tryFrom($data['ty'] ?? '');

        switch ($styleTy) {
            case ShapeType::Stroke:
                $elementData = new SVGStrokeStyleData(
                    $this,
                    $data,
                    $styleOb
                );

                break;

            case ShapeType::Fill:
                $elementData = new SVGFillStyleData(
                    $this,
                    $data,
                    $styleOb
                );

                break;

            case ShapeType::GradientFill:
            case ShapeType::GradientStroke:
                $GradientConstructor
                  = ShapeType::GradientFill === $styleTy
                    ? SVGGradientFillStyleData::class
                    : SVGGradientStrokeStyleData::class;

                $elementData = new $GradientConstructor(
                    $this,
                    $data,
                    $styleOb
                );
                if ($elementData->gf) {
                    $this->globalData['defs']?->appendChild($elementData->gf);
                }

                if ($elementData->maskId && $elementData->ms && $elementData->of) {
                    $this->globalData['defs']?->appendChild($elementData->ms);
                    $this->globalData['defs']?->appendChild($elementData->of);
                    $locationHref = LocationHref::getHref();
                    $pathElement->setAttribute(
                        'mask',
                        "url({$locationHref}#{$elementData->maskId})"
                    );
                }

                break;

            case ShapeType::NoStyle:
                $elementData = new SVGNoStyleData(
                    $this,
                    $data,
                    $styleOb
                );
        }

        if (ShapeType::Stroke === $styleTy || ShapeType::GradientStroke === $styleTy) {
            $pathElement->setAttribute('stroke-linecap', lineCapEnum($data['lc'] ?? 2));
            $pathElement->setAttribute('stroke-linejoin', lineJoinEnum($data['lj'] ?? 2));
            $pathElement->setAttribute('fill-opacity', '0');
            if (1 === $data['lj'] && $data['ml']) {
                $pathElement->setAttribute('stroke-miterlimit', $data['ml']);
            }
        }

        if (2 === ($data['r'] ?? null)) {
            $pathElement->setAttribute('fill-rule', 'evenodd');
        }

        if (!empty($data['ln'] ?? null)) {
            $pathElement->setAttribute('id', $data['ln']);
        }
        if (!empty($data['cl'] ?? null)) {
            addClasses($pathElement, [$data['cl']]);
        }
        if (!empty($data['bm'] ?? null)) {
            addStyles($pathElement, ['mix-blend-mode' => getBlendMode($data['bm'])]);
        }
        array_push($this->stylesList, $styleOb);
        if ($elementData) {
            $this->addToAnimatedContents($data, $elementData);
        }

        // if ($data['ty'] === ShapeType::GradientFill) {
        //   console.log(elementData)
        // }

        return [
            'style' => $styleOb,
            'element' => $elementData,
        ];
    }

    public function createTransformElement(array $data, \DOMElement $container)
    {
        $transformProperty = TransformPropertyFactory::getTransformProperty(
            $this,
            $data,
            $this
        );

        if (!$transformProperty->o) {
            throw new \Error(get_class($this).': Missing required data in TransformProperty');
        }
        $elementData = new SVGTransformData(
            $transformProperty,
            $transformProperty->o,
            $container
        );

        $this->addToAnimatedContents($data, $elementData);

        return $elementData;
    }

    public function destroy()
    {
        $this->destroyBaseElement();
        $this->shapesData = null;
        $this->itemsData = null;
    }

    /**
     * This method searches for multiple shapes that affect a single element and one of them is animated.
     */
    public function filterUniqueShapes()
    {
        $length = count($this->shapes);
        $jLen = count($this->stylesList);
        $tempShapes = [];
        $style = null;
        $areAnimated = null;

        for ($j = 0; $j < $jLen; ++$j) {
            $style = $this->stylesList[$j];
            $areAnimated = false;
            $tempShapes = [];
            for ($i = 0; $i < $length; ++$i) {
                $shape = $this->shapes[$i] ?? null;
                if (!$shape) {
                    continue;
                }

                if (in_array($style, $shape->styles ?? [], true)) {
                    array_push($tempShapes, $shape);
                    $areAnimated = (bool) ($shape->_isAnimated ?? $areAnimated);
                }
            }
            if (count($tempShapes) > 1 && $areAnimated) {
                $this->setShapesAsAnimated($tempShapes);
            }
        }
    }

    public function initSecondaryElement()
    {
        // Pass through
    }

    public function reloadShapes()
    {
        if ($this->_reloadingShapes) {
            return;
        }
        if (!$this->layerElement) {
            throw new \Error(get_class($this).': Could not access layerElement');
        }
        $this->_reloadingShapes = true;
        $this->shapeModifiers = [];
        $this->_isFirstFrame = true;
        $length = count($this->itemsData);

        for ($i = 0; $i < $length; ++$i) {
            $this->prevViewData[$i] = $this->itemsData[$i];
        }
        $this->searchShapes(
            $this->shapesData,
            $this->itemsData,
            $this->prevViewData,
            $this->layerElement,
            0,
            [],
            true,
            []
        );
        $this->filterUniqueShapes();
        $this->rebindShapeModifiers();
        $dLength = count($this->dynamicProperties);

        for ($i = 0; $i < $dLength; ++$i) {
            $this->dynamicProperties[$i]?->getValue();
        }
        $this->renderModifiers();
        $this->_reloadingShapes = false;
    }

    public function renderInnerContent()
    {
        $this->debugLog(
            'renderInnerContent start: shapes='.count($this->shapes ?? [])
            .' stylesList='.count($this->stylesList ?? [])
            .' animatedContents='.count($this->animatedContents ?? [])
        );
        $this->renderModifiers();
        $length = count($this->stylesList);

        for ($i = 0; $i < $length; ++$i) {
            $this->stylesList[$i]?->reset();
        }
        $this->renderShape();
        for ($i = 0; $i < $length; ++$i) {
            if (!$this->stylesList[$i]?->_mdf && !$this->_isFirstFrame) {
                continue;
            }
            if ($this->stylesList[$i]?->msElem) {
                $this->stylesList[$i]?->msElem?->setAttribute('d', $this->stylesList[$i]?->d ?? '');
                // Adding M0 0 fixes same mask bug on all browsers
                $maskPathString = $this->stylesList[$i]?->d ?? '';
                $this->stylesList[$i]->d = "M0 0{$maskPathString}";
            }
            $this->stylesList[$i]?->pElem?->setAttribute('d', $this->stylesList[$i]?->d ?: 'M0 0');
        }
    }

    public function renderShape()
    {
        $length = count($this->animatedContents);

        for ($i = 0; $i < $length; ++$i) {
            $data = $this->animatedContents[$i]['data'] ?? null;
            $element = $this->animatedContents[$i]['element'] ?? null;
            $fn = $this->animatedContents[$i]['fn'] ?? null;

            if (
                !$this->_isFirstFrame
                && !$element?->_isAnimated
                || true === $data
                || !$fn
            ) {
                continue;
            }
            $fn(
                $data,
                $element,
                $this->_isFirstFrame
            );
        }
    }

    public function searchShapes(
        array &$arr,
        array &$itemsData,
        array &$prevViewData,
        \DOMElement $container,
        int $level,
        array $transformers,
        bool $shouldRenderFromProps,
        array $styles = []
    ) {
        $shouldRender = $shouldRenderFromProps;
        // Make a clone of transfomers
        $ownTransformers = [...$transformers];
        // Inherit styles from parent groups. This is required when a group contains
        // a nested group with paths, while fill/stroke styles are siblings of that nested group.
        $ownStyles = [...$styles];
        $inheritedStylesCount = count($ownStyles);
        $ownModifiers = [];
        $currentTransform = null;
        $Modifier = null;
        $length = count($arr);

        for ($i = $length - 1; $i >= 0; --$i) {
            $processedPos = $this->searchProcessedElement($arr[$i]);

            if ($processedPos && isset($prevViewData[$processedPos - 1])) {
                $itemsData[$i] = $prevViewData[$processedPos - 1];
            } else {
                $processedPos = 0;
                // Repeater clone groups manage their own visibility; do not inherit
                // `shouldRender = false` from the repeater item during searchShapes.
                if (!isset($arr[$i]['_rpId'])) {
                    $arr[$i]['_shouldRender'] = $shouldRender;
                }
            }

            $shapeType = ShapeType::tryFrom($arr[$i]['ty'] ?? '');

            switch ($shapeType) {
                case ShapeType::Fill:
                case ShapeType::Stroke:
                case ShapeType::GradientFill:
                case ShapeType::GradientStroke:
                case ShapeType::NoStyle:
                    if ($processedPos) {
                        ['style' => $style] = $itemsData[$i] ?? ['style' => null];

                        if ($style) {
                            $style->closed = false;
                        }
                    } else {
                        $itemsData[$i] = $this->createStyleElement(
                            $arr[$i],
                            $level
                        );
                    }
                    $pElem = $itemsData[$i]['style']?->pElem ?? null;

                    if (($arr[$i]['_shouldRender'] ?? null) && $pElem?->parentNode !== $container) {
                        if ($pElem) {
                            $container->appendChild($pElem);
                        }
                    }
                    array_push($ownStyles, $itemsData[$i]['style'] ?? null);

                    break;

                case ShapeType::Group:
                    if ($processedPos) {
                        $jLen = count($itemsData[$i]['it'] ?? []);

                        for ($j = 0; $j < $jLen; ++$j) {
                            ['it' => $it, 'prevViewData' => $pr] = $itemsData[$i] ?? [
                                'it' => null,
                                'prevViewData' => null,
                            ];

                            if (!$pr || !$it) {
                                continue;
                            }

                            $pr[$j] = $it[$j]['DataInterface'];
                        }
                    } else {
                        $itemsData[$i] = $this->createGroupElement($arr[$i]);
                    }
                    if (!isset($arr[$i]['it']) || !is_array($arr[$i]['it'])) {
                        $arr[$i]['it'] = [];
                    }
                    if (!isset($itemsData[$i]['it']) || !is_array($itemsData[$i]['it'])) {
                        $itemsData[$i]['it'] = [];
                    }
                    if (!isset($itemsData[$i]['prevViewData']) || !is_array($itemsData[$i]['prevViewData'])) {
                        $itemsData[$i]['prevViewData'] = [];
                    }
                    $this->searchShapes(
                        $arr[$i]['it'],
                        $itemsData[$i]['it'],
                        $itemsData[$i]['prevViewData'],
                        $itemsData[$i]['gr'] ?? null,
                        $level + 1,
                        $ownTransformers,
                        $shouldRender,
                        $ownStyles
                    );

                    if ($arr[$i]['_shouldRender'] ?? null && ($itemsData[$i]['gr']?->parentNode ?? null) !== $container) {
                        ['gr' => $gr] = $itemsData[$i] ?? [];

                        if ($gr) {
                            $container->appendChild($gr);
                        }
                    }

                    break;

                case ShapeType::Transform:
                    if (!$processedPos) {
                        $itemsData[$i] = $this->createTransformElement($arr[$i], $container);
                    }
                    $currentTransform = $itemsData[$i]?->transform ?? null;
                    if ($currentTransform) {
                        array_push($ownTransformers, $currentTransform);
                    }

                    break;

                case ShapeType::Path:
                case ShapeType::Rectangle:
                case ShapeType::Ellipse:
                case ShapeType::PolygonStar:
                    if (!$processedPos) {
                        $itemsData[$i] = $this->createShapeElement(
                            $arr[$i],
                            $ownTransformers,
                            $level
                        );
                    }
                    $this->setElementStyles($itemsData[$i], $ownStyles);

                    break;

                case ShapeType::Trim:
                case ShapeType::RoundedCorners:
                case ShapeType::Unknown:
                case ShapeType::PuckerBloat:
                case ShapeType::ZigZag:
                case ShapeType::OffsetPath:
                    if ($processedPos) {
                        $Modifier = $itemsData[$i];
                        $Modifier->closed = false;
                        $this->registerShapeModifier($Modifier, false);
                    } else {
                        $Modifier = ShapeModifiers::getModifier($shapeType ?? ShapeType::Unknown);
                        $Modifier->init($this, $arr[$i]);
                        $itemsData[$i] = $Modifier;
                        $this->registerShapeModifier($Modifier, true);
                    }
                    array_push($ownModifiers, $Modifier);

                    break;

                case ShapeType::Repeater:
                    if ($processedPos) {
                        $Modifier = $itemsData[$i];
                        $Modifier->closed = true;
                        $Modifier->pos = $i;
                        $Modifier->syncGroupsFromArray();
                        // Re-bind after reloadShapes(); parent group itemsData may have been recreated.
                        $Modifier->elemsData = &$itemsData;
                        $this->registerShapeModifier($Modifier, false);
                        array_push($ownModifiers, $Modifier);

                        break;
                    }
                    $Modifier = ShapeModifiers::getModifier($shapeType ?? ShapeType::Unknown);
                    $itemsData[$i] = $Modifier;
                    $Modifier->initRepeater(
                        $this,
                        $arr,
                        $i,
                        $itemsData
                    );
                    $this->registerShapeModifier($Modifier, true);

                    $shouldRender = false;
                    array_push($ownModifiers, $Modifier);

                    break;

                default:
            }

            $this->addProcessedElement(
                $arr[$i],
                $i + 1
            );
        }
        $sLen = count($ownStyles);

        for ($i = 0; $i < $sLen; ++$i) {
            // Only close styles created at this recursion level.
            // Inherited styles must remain open because we traverse siblings backwards.
            if ($i < $inheritedStylesCount) {
                continue;
            }
            $ownStyle = $ownStyles[$i];

            if ($ownStyle) {
                $ownStyle->closed = true;
            }
        }

        $mLen = count($ownModifiers);

        for ($i = 0; $i < $mLen; ++$i) {
            $ownModifiers[$i]->closed = true;
        }
    }

    public function setElementStyles(SVGShapeData $elementData, ?array $styles = null)
    {
        $stylesToUse = $styles ?? $this->stylesList;
        $length = count($stylesToUse);

        for ($i = 0; $i < $length; ++$i) {
            if (!$stylesToUse[$i]?->closed) {
                array_push($elementData->styles, $stylesToUse[$i]);
            }
        }
    }

    public function setShapesAsAnimated(array $shapes)
    {
        $length = count($shapes);

        for ($i = 0; $i < $length; ++$i) {
            $shapes[$i]?->setAsAnimated();
        }
    }

    private bool $_reloadingShapes = false;
}
