<?php

namespace LottiePHP\Elements;

use function LottiePHP\Utils\addClasses;
use function LottiePHP\Utils\addStyles;
use function LottiePHP\Utils\createSizedArray;
use function LottiePHP\Utils\createTag;
use function LottiePHP\Utils\normalizePathDataTangents;

class SVGTextLottieElement extends TextElement
{
    use SVGBaseTraits;
    public ?bool $_sizeChanged;
    public ?array $bbox;
    public $renderedLetters = [];
    public ?\DOMElement $textContainer;
    public $textSpans = [];

    /**
     * @param array[] $items
     *
     * @return array[]
     */
    private function ensureShapeGroupPaint(array $items, array $fc): array
    {
        foreach ($items as $i => $item) {
            if (($item['ty'] ?? '') === 'gr' && isset($item['it']) && is_array($item['it'])) {
                $items[$i]['it'] = $this->ensureShapeGroupPaint($item['it'], $fc);
            }
        }
        $hasPaint = false;
        foreach ($items as $item) {
            if (in_array($item['ty'] ?? '', ['fl', 'st', 'gf', 'gs'], true)) {
                $hasPaint = true;

                break;
            }
        }
        // `searchShapes` walks `it` from last index to first. Insert the fill immediately
        // before the group transform so the path is styled inside the transformed subtree.
        if (!$hasPaint) {
            $insertAt = count($items);
            foreach ($items as $idx => $item) {
                if (($item['ty'] ?? '') === 'tr') {
                    $insertAt = $idx;

                    break;
                }
            }
            array_splice($items, $insertAt, 0, [$this->syntheticGlyphFill($fc)]);
        }

        return $items;
    }

    /**
     * Font `chars` outlines are often a single path with no fl/st in the group. The SVG shape
     * renderer only accumulates path geometry into style elements, so with no fill the path
     * string is never written. Mirror After Effects by prepending a fill that matches the text
     * layer color.
     *
     * @param array[] $shapes
     *
     * @return array[]
     */
    private function injectGlyphFillsIfMissing(array $shapes, array $fc): array
    {
        foreach ($shapes as $i => $node) {
            if (($node['ty'] ?? '') === 'gr' && isset($node['it']) && is_array($node['it'])) {
                $shapes[$i]['it'] = $this->ensureShapeGroupPaint($node['it'], $fc);
            }
        }

        return $shapes;
    }

    /**
     * Clone glyph shape data for rendering without mutating the font cache.
     * Some JSON exports bake font size into a `tr` scale and use absolute bezier points.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function prepareGlyphData(array $data): array
    {
        if (!isset($data['shapes']) || !is_array($data['shapes'])) {
            return $data;
        }

        $data = json_decode(json_encode($data), true);
        if (!is_array($data)) {
            return ['shapes' => []];
        }

        foreach ($data['shapes'] as $shapeIndex => $shape) {
            if (($shape['ty'] ?? '') !== 'gr' || !isset($shape['it']) || !is_array($shape['it'])) {
                continue;
            }

            foreach ($data['shapes'][$shapeIndex]['it'] as $itemIndex => $item) {
                // Scale is applied on childSpan (finalSize / 100). Neutralize baked-in glyph
                // `tr` scales from JSON exports so we do not multiply with childSpan.
                if (($item['ty'] ?? '') === 'tr' && isset($item['s']['k']) && is_array($item['s']['k'])) {
                    $data['shapes'][$shapeIndex]['it'][$itemIndex]['s']['k'] = [100, 100];
                }
                if (
                    ($item['ty'] ?? '') === 'sh'
                    && isset($item['ks']['k'])
                    && is_array($item['ks']['k'])
                ) {
                    $data['shapes'][$shapeIndex]['it'][$itemIndex]['ks']['k']
                        = normalizePathDataTangents($item['ks']['k']);
                }
            }
        }

        return $data;
    }

    /**
     * JSON glyph exports can leave a duplicate unstylized path sibling next to the
     * filled path inside the shape group. Drop paths with no paint attributes.
     */
    private function removeUnstyledGlyphPaths(\DOMElement $glyphRoot): void
    {
        $paths = $glyphRoot->getElementsByTagName('path');
        for ($i = $paths->length - 1; $i >= 0; --$i) {
            $path = $paths->item($i);
            if (!$path instanceof \DOMElement) {
                continue;
            }
            if (!$path->hasAttribute('fill') && !$path->hasAttribute('stroke')) {
                $path->parentNode?->removeChild($path);
            }
        }
    }

    private function syntheticGlyphFill(array $fc): array
    {
        $r = $fc[0] ?? 0;
        $g = $fc[1] ?? 0;
        $b = $fc[2] ?? 0;

        return [
            'ty' => 'fl',
            'nm' => 'glyph-fill',
            'c' => [
                'a' => 0,
                'k' => [$r, $g, $b, 1],
            ],
            'o' => [
                'a' => 0,
                'k' => 100,
            ],
            'r' => 1,
            'hd' => false,
        ];
    }

    public function __construct(
        array $data,
        array $globalData,
        BaseElement $comp,
    ) {
        parent::__construct();

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

        if (!$this->textProperty->currentData ?? null) {
            throw new \Error(get_class($this).': DocumentData is not implemented');
        }

        $this->addDynamicProperty($this);
        $i = 0;
        $len = 0;

        $documentData = $this->textProperty->currentData;

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
        $hasGlyphs = (bool) ($this->globalData['fontManager']?->chars ?? null);
        // Some optimized exports include font metadata but omit `chars` glyph data.
        // If we can't resolve *any* glyph for this layer, fall back to SVG <text>/<tspan>.
        if ($hasGlyphs) {
            $fontFamily = $this->globalData['fontManager']?->getFontByName($documentData['f'])['fFamily'] ?? null;
            $hasRenderableGlyph = false;
            foreach (($documentData['finalText'] ?? []) as $ch) {
                if ('' === $ch || ' ' === $ch || "\r" === $ch) {
                    continue;
                }
                $charData = $this->globalData['fontManager']?->getCharData(
                    $ch,
                    $fontData['fStyle'] ?? null,
                    $fontFamily
                );
                if (!empty($charData['data']['shapes'] ?? null)) {
                    $hasRenderableGlyph = true;

                    break;
                }
            }
            if (!$hasRenderableGlyph) {
                $hasGlyphs = false;
            }
        }

        $len = count($letters);

        /** @var null|\DOMElement $tSpan */
        $tSpan = null;
        $matrixHelper = $this->mHelper;
        $shapeStr = '';

        $isSingleShape = $this->data['singleShape'] ?? false;
        $xPos = 0;
        $yPos = 0;
        $isFirstLine = true;
        $trackingOffset
          = $documentData['tr'] * 0.001 * (float) $documentData['finalSize'];

        // If we decided to fall back to <text>/<tspan> at runtime, make sure the container exists.
        if ($isSingleShape && !$hasGlyphs && !$documentData['sz'] && !$this->textContainer) {
            $this->textContainer = createTag('text');
        }

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
                $tSpan = $this->textSpans[$i]['span'] ?? createTag('tspan');
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
                $yPos += (float) $documentData['finalLineHeight'];
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
                        : createTag($hasGlyphs ? 'g' : 'text')) ?? null;

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
                            $childSpan = createTag('g');

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
                        $yPos += (float) $documentData['yOffset'];
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
                            $data = $this->prepareGlyphData($charData['data']);
                            if (!empty($documentData['fc'])) {
                                $data['shapes'] = $this->injectGlyphFillsIfMissing(
                                    $data['shapes'],
                                    $documentData['fc']
                                );
                            }
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
                    $glyphElement->prepareFrame(0);
                    $glyphElement->renderFrame();
                    if ($glyphElement->layerElement) {
                        $this->removeUnstyledGlyphPaths($glyphElement->layerElement);
                        $this->textSpans[$i]['childSpan']?->appendChild($glyphElement->layerElement);
                    }

                    // Glyph outlines use ~100 design units per em (same basis as `chars[].w`). Map to
                    // document pixels — lottie-web does this for t===1 comps; plain path glyphs often
                    // omit an inner `tr`, so buildShapeData cannot set scale on the last `it` item.
                    $glyphScale = ((float) ($documentData['finalSize'] ?? 100)) / 100.0;
                    $this->textSpans[$i]['childSpan']?->setAttribute(
                        'transform',
                        sprintf('scale(%s,%s)', $glyphScale, $glyphScale)
                    );

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
                $currentTextContent .= $textArray[$i] ?? '';
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
        if (($this->data['singleShape'] ?? false) && !$this->globalData['fontManager']?->chars) {
            $this->textContainer = createTag('text');
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
                $glyphElement->prepareFrame((float) $this->comp?->renderedFrame - $this->data['st']);
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
        if ($this->data['singleShape'] ?? false && !$this->_mdf) {
            return;
        }
        if (!$this->textProperty) {
            throw new \Error(get_class($this).': textProperty is not initialized');
        }
        $this->textAnimator?->getMeasures(
            $this->textProperty->currentData,
            $this->lettersChangedFlag
        );
        if ($this->lettersChangedFlag || $this->textAnimator?->lettersChangedFlag) {
            $this->_sizeChanged = true;
            $renderedLetters = $this->textAnimator?->renderedLetters;
            $letters = $this->textProperty->currentData['l'];
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
                    $textSpan?->setAttribute('stroke-width', $renderedLetter->sw ?? 0);
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
        $this->prepareFrame((float) $this->comp->renderedFrame - $this->data['st']);
        $this->renderInnerContent();

        return null;
    }

    private $emptyShapeData = ['shapes' => []];
}
