<?php

namespace LottiePHP\Elements;

use LottiePHP\Utils\Enums\RendererType;

use function LottiePHP\Utils\addClasses;
use function LottiePHP\Utils\addStyles;
use function LottiePHP\Utils\createNS;
use function LottiePHP\Utils\createSizedArray;

class SVGTextLottieElement extends TextElement
{
    use SVGBaseTraits;
    public ?bool $_sizeChanged;
    public ?array $bbox;
    public ?int $renderedFrame;
    public $renderedLetters = [];
    public ?\DOMElement $textContainer;
    public $textSpans = [];

    private $emptyShapeData = ['shapes' => []];

    public function __construct(
        array $data,
        array $globalData,
        SVGCompElement $comp
    ) {
        $this->renderType = RendererType::SVG;

        $this->initElement(
            $data,
            $globalData,
            $comp
        );
    }

    public function buildNewText()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }

        if (!$this->globalData) {
            throw new \Error(get_class($this).': globalData is not implemented');
        }

        if (!$this->layerElement) {
            throw new \Error(get_class($this).': layerElement is not implemented');
        }

        if (!$this->textProperty['currentData'] ?? null) {
            throw new \Error(get_class($this).': DocumentData is not implemented');
        }

        $this->addDynamicProperty($this);
        $i = 0;
        $len = 0;

        $documentData = $this->textProperty['currentData'];

        $this->renderedLetters = createSizedArray(count($documentData['l'] ?? 0));
        if ($documentData['fc']) {
            $this->layerElement->setAttribute(
                'fill',
                $this->buildColor($documentData['fc'])
            );
        } else {
            $this->layerElement->setAttribute('fill', 'rgba(0,0,0,0)');
        }
        if ($documentData['sc']) {
            $this->layerElement->setAttribute('stroke', $this->buildColor($documentData['sc']));
            $this->layerElement->setAttribute('stroke-width', $documentData['sw'] ?? 0);
        }
        $this->layerElement->setAttribute(
            'font-size',
            $documentData['finalSize'] ?? 0
        );
        $fontData = $this->globalData['fontManager']?->getFontByName($documentData['f']);

        if ($fontData['fClass'] ?? null) {
            addClasses($this->layerElement, [$fontData['fClass']]);
        } else {
            if ($fontData['fFamily'] ?? null) {
                $this->layerElement->setAttribute('font-family', $fontData['fFamily']);
            }
            ['fWeight' => $fWeight, 'fStyle' => $fStyle] = $documentData;

            $this->layerElement->setAttribute('font-style', $fStyle);
            $this->layerElement->setAttribute('font-weight', $fWeight);
        }
        $this->layerElement->setAttribute('aria-label', $documentData['t']);

        $letters = $documentData['l'];
        $hasGlyphs = (bool) $this->globalData['fontManager']['chars'] ?? null;

        $len = count($letters);

        /** @var null|\DOMElement $tSpan */
        $tSpan = null;
        $matrixHelper = $this->mHelper;
        $shapeStr = '';

        ['singleShape' => $singleShape, 'isSingleShape' => $isSingleShape] = $this->data;
        $xPos = 0;
        $yPos = 0;
        $isFirstLine = true;
        $trackingOffset
          = $documentData['tr'] * 0.001 * (int) $documentData['finalSize'];

        if ($isSingleShape && !$hasGlyphs && !$documentData['sz']) {
            $tElement = $this->textContainer;

            $justify = 'start';

            switch ($documentData['j']) {
                case 1:
                    $justify = 'end';

                    break;

                case 2:
                    $justify = 'middle';

                    break;

                default:
                    $justify = 'start';

                    break;
            }
            $tElement?->setAttribute('text-anchor', $justify);
            $tElement?->setAttribute('letter-spacing', $trackingOffset);
            $textContent = $this->buildTextContents($documentData['finalText']);

            $len = count($textContent);
            $yPos = $documentData['ps']
              ? $documentData['ps'][1] + (int) ($documentData['ascent'])
              : 0;
            for ($i = 0; $i < $len; ++$i) {
                $tSpan = $this->textSpans[$i]['span'] ?? createNS('tspan');
                $tSpan->textContent = $textContent[$i] ?? '';
                $tSpan->setAttribute('x', '0');
                $tSpan->setAttribute('y', $yPos);
                addStyles($tSpan, ['display' => 'inherit']);
                $tElement?->appendChild($tSpan);
                $this->textSpans[$i] ??= [
                    'glyph' => null,
                    'span' => null,
                ];
                $this->textSpans[$i]['span'] = $tSpan;
                $yPos += (int) $documentData['finalLineHeight'];
            }

            if ($tElement) {
                $this->layerElement->appendChild($tElement);
            }
        } else {
            $cachedSpansLength = count($this->textSpans);
            $charData = null;

            for ($i = 0; $i < $len; ++$i) {
                $this->textSpans[$i] ??= [
                    'childSpan' => null,
                    'glyph' => null,
                    'span' => null,
                ];
                if (!$hasGlyphs || !$isSingleShape || 0 === $i) {
                    $tSpan
                      = ($cachedSpansLength > $i
                        ? $this->textSpans[$i]['span'] ?? null
                        : createNS($hasGlyphs ? 'g' : 'text')) ?? null;

                    if (!$tSpan) {
                        throw new \Error('Could not create tSpan');
                    }

                    if ($cachedSpansLength <= $i) {
                        $tSpan->setAttribute('stroke-linecap', 'butt');
                        $tSpan->setAttribute('stroke-linejoin', 'round');
                        $tSpan->setAttribute('stroke-miterlimit', '4');
                        if ($this->textSpans[$i]) {
                            $this->textSpans[$i]['span'] = $tSpan;
                        }

                        if ($hasGlyphs) {
                            $childSpan = createNS('g');

                            $tSpan->appendChild($childSpan);

                            if ($this->textSpans[$i]) {
                                $this->textSpans[$i]['childSpan'] = $childSpan;
                            }
                        }

                        if ($this->textSpans[$i]) {
                            $this->textSpans[$i]['span'] = $tSpan;
                        }

                        $this->layerElement->appendChild($tSpan);
                    }
                    addStyles($tSpan, ['display' => 'inherit']);
                }

                $matrixHelper->reset();
                if ($isSingleShape) {
                    if ($letters[$i]['n'] ?? null) {
                        $xPos = -$trackingOffset;
                        $yPos += (int) $documentData['yOffset'];
                        $yPos += $isFirstLine ? 1 : 0;
                        $isFirstLine = false;
                    }
                    $this->applyTextPropertiesToMatrix(
                        $documentData,
                        $matrixHelper,
                        $letters[$i]['line'] ?? 0,
                        $xPos,
                        $yPos
                    );
                    $xPos += $letters[$i]['l'] ?? 0;
                    // xPos += letters[$i].val === ' ' ? 0 : trackingOffset;
                    $xPos += $trackingOffset;
                }
                if ($hasGlyphs) {
                    $charData = $this->globalData['fontManager']?->getCharData(
                        $documentData['finalText'][$i] ?? '',
                        $fontData['fStyle'] ?? null,
                        $this->globalData['fontManager']->getFontByName($documentData['f'])['fFamily']
                    );
                    $glyphElement = null;

                    // t === 1 means the character has been replaced with an animated shaped
                    if ($charData['t'] ?? 0 === 1) {
                        $glyphElement = new SVGCompElement(
                            $charData['data'],
                            $this->globalData,
                            $this
                        );
                    } else {
                        $data = $this->emptyShapeData;

                        if ($charData['data']['shapes'] ?? null) {
                            $data = $this->buildShapeData(
                                $charData['data'],
                                (int) $documentData['finalSize']
                            );
                        }
                        $glyphElement = new SVGShapeElement(
                            $data,
                            $this->globalData,
                            $this
                        );
                    }
                    if ($this->textSpans[$i]) {
                        ['glyph' => $glyph] = $this->textSpans[$i] ?? [];

                        if ($glyph) {
                            if ($glyph->layerElement) {
                                $this->textSpans[$i]['childSpan']?->removeChild($glyph->layerElement);
                            }

                            $glyph->destroy();
                        }

                        $this->textSpans[$i]['glyph'] = $glyphElement;
                    }
                    $glyphElement->_debug = true;
                    $glyphElement->prepareFrame(0);
                    $glyphElement->renderFrame();
                    if ($glyphElement->layerElement) {
                        $this->textSpans[$i]['childSpan']?->appendChild($glyphElement->layerElement);
                    }

                    // when using animated shapes, the layer will be scaled instead of replacing the internal scale
                    // $this might have issues with strokes and might need a different solution
                    if ($charData['t'] ?? 0 === 1) {
                        $scale = ((int) $documentData['finalSize']) / 100;
                        $this->textSpans[$i]['childSpan']?->setAttribute('transform', "scale({$scale},{$scale})");
                    }

                    continue;
                }
                if ($tSpan) {
                    if ($isSingleShape) {
                        $translate = implode(',', [$matrixHelper->props[12], $matrixHelper->props[13]]);
                        $tSpan->setAttribute('transform', "translate({$translate})");
                    }

                    $tSpan->textContent = $letters[$i]['val'] ?? '';
                    addStyles($tSpan, ['white-space' => 'preserve']);

                    // $tSpan->setAttributeNS(
                    //   namespaceXML,
                    //   'xml:space',
                    //   'preserve'
                    // )
                }
            }
            if ($isSingleShape) {
                $tSpan?->setAttribute('d', $shapeStr);
            }
        }
        $span = $this->textSpans[$i]['span'] ?? null;

        if ($span) {
            while ($i < count($this->textSpans)) {
                addStyles($span, ['display' => 'none']);

                ++$i;
            }
        }

        $this->_sizeChanged = true;
    }

    public function buildShapeData(array $data, int $scale)
    {
        // data should probably be cloned to apply scale separately to each instance of a text on different layers
        // but since text internal content gets only rendered once and then it's never rerendered,
        // it's probably safe not to clone data and reuse always the same instance even if the object is mutated.
        // Avoiding cloning is preferred since cloning each character shape data is expensive
        if (count($data['shapes']) > 0) {
            $shape = $data['shapes'][0];

            if ($shape['it'] ?? null) {
                $shapeItem = $shape['it'][count($shape['it']) - 1];

                if ($shapeItem['s'] ?? null) {
                    $shapeItem['s']['k'][0] = $scale;
                    $shapeItem['s']['k'][1] = $scale;
                }
            }
        }

        return $data;
    }

    public function buildTextContents(array $textArray)
    {
        $i = 0;
        $length = count($textArray);
        $textContents = [];
        $currentTextContent = '';

        while ($i < $length) {
            if (
                $textArray[$i] === chr(13)
                || $textArray[$i] === chr(3)
            ) {
                array_push($textContents, $currentTextContent);
                $currentTextContent = '';
            } else {
                $currentTextContent += $textArray[$i] ?? '';
            }
            ++$i;
        }
        array_push($textContents, $currentTextContent);

        return $textContents;
    }

    public function createContent()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        if (!$this->globalData) {
            throw new \Error(get_class($this).': globalData is not implemented');
        }
        if ($this->data['singleShape'] && !$this->globalData['fontManager']?->chars) {
            $this->textContainer = createNS('text');
        }
    }

    public function getValue()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        $length = count($this->textSpans);
        $glyphElement = null;

        $this->renderedFrame = $this->comp?->renderedFrame;
        for ($i = 0; $i < $length; ++$i) {
            $glyphElement = $this->textSpans[$i]['glyph'] ?? null;
            if ($glyphElement) {
                $glyphElement->prepareFrame((int) $this->comp?->renderedFrame - $this->data['st']);
                if ($glyphElement->_mdf) {
                    $this->_mdf = true;
                }
            }
        }
    }

    public function renderInnerContent()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        $this->validateText();
        if ($this->data['singleShape'] && !$this->_mdf) {
            return;
        }
        if (!$this->textProperty) {
            throw new \Error(get_class($this).': textProperty is not initialized');
        }
        $this->textAnimator?->getMeasures(
            $this->textProperty['currentData'],
            $this->lettersChangedFlag
        );
        if ($this->lettersChangedFlag || $this->textAnimator?->lettersChangedFlag) {
            $this->_sizeChanged = true;
            $renderedLetters = $this->textAnimator?->renderedLetters;
            $letters = $this->textProperty['currentData']['l'];
            $length = count($letters);
            $renderedLetter = null;
            $textSpan = null;
            $glyphElement = null;

            for ($i = 0; $i < $length; ++$i) {
                if ($letters[$i]['n'] ?? null) {
                    continue;
                }
                $renderedLetter = $renderedLetters[$i] ?? null;
                $textSpan = $this->textSpans[$i]['span'] ?? null;
                $glyphElement = $this->textSpans[$i]['glyph'] ?? null;
                if ($glyphElement) {
                    $glyphElement->renderFrame();
                }
                if ($renderedLetter?->_mdf['m']) {
                    $textSpan?->setAttribute('transform', $renderedLetter->m);
                }
                if ($renderedLetter?->_mdf['o']) {
                    $textSpan?->setAttribute('opacity', $renderedLetter->o ?? 1);
                }
                if ($renderedLetter?->_mdf['sw']) {
                    $textSpan?->setAttribute('stroke-width', $renderedLetter->sw || 0);
                }
                if ($renderedLetter?->_mdf['sc']) {
                    $textSpan?->setAttribute('stroke', $renderedLetter->sc);
                }
                if ($renderedLetter?->_mdf['fc']) {
                    $textSpan?->setAttribute('fill', $renderedLetter->fc);
                }
            }
        }
    }

    public function sourceRectAtTime()
    {
        if (!$this->data) {
            throw new \Error(get_class($this).': data (LottieLayer) is not implemented');
        }
        if (!$this->comp) {
            throw new \Error(get_class($this).': comp (ElementInterface) is not implemented');
        }
        if (!$this->layerElement) {
            throw new \Error(get_class($this).': layerElement is not implemented');
        }
        $this->prepareFrame((int) $this->comp->renderedFrame - $this->data['st']);
        $this->renderInnerContent();
        // if ($this->_sizeChanged) {
        //     $this->_sizeChanged = false;
        //     $textBox = $this->layerElement->getBBox();

        //     $this->bbox = [
        //       'height' => $textBox['height'],
        //       'left' => $textBox['x'],
        //       'top' => $textBox['y'],
        //       'width' => $textBox['width'],
        //     ];

        //     return $this->bbox;
        // }

        return null;
    }
}
