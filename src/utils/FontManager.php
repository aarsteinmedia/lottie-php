<?php

namespace LottiePHP\Utils;

use LottiePHP\Utils\Enums\RendererType;

use function LottiePHP\Utils\Pooling\domDocumentPool;

class FontManager
{
    public ?array $chars = null;
    public array $fonts = [];
    public $isLoaded = false;
    public $typekitLoaded = 0;

    private function checkLoadedFonts(): void
    {
        $node = null;
        $w = 0;
        $loadedCount = count($this->fonts);

        for ($i = 0; $i < $loadedCount; ++$i) {
            $font = $this->fonts[$i];

            if (!$font) {
                continue;
            }

            if ($font['loaded']) {
                --$loadedCount;

                continue;
            }

            if ('n' === $font['fOrigin'] || 0 === $font['origin']) {
                $font['loaded'] = true;

                continue;
            }
            $node = $font['monoCase']['node'] ?? null;
            $w = $font['monoCase']['w'] ?? 0;
            if ($node?->offsetWidth === $w) {
                $node = $font['sansCase']['node'] ?? null;
                $w = $font['sansCase']['w'] ?? 0;
                if ($node?->offsetWidth !== $w) {
                    --$loadedCount;
                    $font['loaded'] = true;
                }
            } else {
                --$loadedCount;
                $font['loaded'] = true;
            }
            if (!$font['loaded']) {
                continue;
            }

            ['monoCase' => $monoCase, 'sansCase' => $sansCase] = $font;

            if ($sansCase) {
                $sansCase->parent->parentNode?->removeChild($sansCase->parent);
            }
            if ($monoCase) {
                $monoCase->parent->parentNode?->removeChild($monoCase->parent);
            }
        }

        if (0 !== $loadedCount && time() - $this->initTime < self::$maxWaitingTime) {
            sleep(0.02);
            $this->checkLoadedFontsBinded();

            return;
        }
        sleep(0.01);
        $this->setIsLoadedBinded();
    }

    private function checkLoadedFontsBinded(...$args)
    {
        return $this->checkLoadedFonts(...$args);
    }

    private function createHelper(
        array $fontData,
        ?\DOMElement $def = null
    ) {
        $fontProps = getFontProperties($fontData);

        $tHelper = createNS('text');

        addStyles($tHelper, ['font-size' => '100px']);

        $tHelper->setAttribute('font-family', $fontData['fFamily']);
        $tHelper->setAttribute('font-style', $fontProps['style']);
        $tHelper->setAttribute('font-weight', $fontProps['weight']);
        $tHelper->textContent = '1';
        if ($fontData['fClass']) {
            addStyles($tHelper, ['font-family' => 'inherit']);
            addClasses($tHelper, [$fontData['fClass']]);
        } else {
            addStyles($tHelper, ['font-family' => $fontData['fFamily']]);
        }
        $def?->appendChild($tHelper);
        self::$helper = $tHelper;

        return [
            'measureText' => function (string $text) {
                self::$helper->textContent = $text;

                return strlen($text);
                // return self::$helper->getComputedTextLength();
            },
        ];
    }

    private static function getCodePoint(string $string): int
    {
        $codePoint = 0;
        $first = charCodeAt($string, 0);

        if ($first >= 0xD800 && $first <= 0xDBFF) {
            $second = charCodeAt($string, 1);

            if ($second >= 0xDC00 && $second <= 0xDFFF) {
                $codePoint = ($first - 0xD800) * 0x400 + $second - 0xDC00 + 0x10000;
            }
        }

        return $codePoint;
    }

    private function setIsLoaded(): void
    {
        $this->isLoaded = true;
    }

    private function setIsLoadedBinded(...$args)
    {
        return $this->setIsLoaded(...$args);
    }

    private static function setUpNode(
        string $font,
        string $family,
    ) {
        $parentNode = domDocumentPool()->createElement('span');
        $parentNode->setAttribute('aria-hidden', 'true');
        addStyles(
            $parentNode,
            [
                'font-family' => $family,
                'position' => 'absolute',
                'left' => '-10000px',
                'top' => '-10000px',
                'font-size' => '300px',
                'font-variant' => 'normal',
                'font-style' => 'normal',
                'font-weight' => 'normal',
                'letter-spacing' => '0',
            ]
        );

        $node = domDocumentPool()->createElement('span');
        $node->textContent = 'giItT1WQy@!-/#';

        $font = self::trimFontOptions($font);

        addStyles(
            $node,
            [
                'font-family' => "{$font}, {$family}",
            ]
        );

        $parentNode->appendChild($node);
        domDocumentPool()->appendChild($parentNode);

        $width = (float) $node->getAttribute('width');

        return [
            'node' => $node,
            'parent' => $parentNode,
            'w' => $width,
        ];
    }

    private static function trimFontOptions(string $font): string
    {
        $familyArray = explode(',', $font);
        $length = count($familyArray);
        $enabledFamilies = [];

        for ($i = 0; $i < $length; ++$i) {
            if ('sans-serif' !== $familyArray[$i] && 'monospace' !== $familyArray[$i]) {
                array_push($enabledFamilies, $familyArray[$i]);
            }
        }

        return implode(',', $enabledFamilies);
    }

    public function __construct()
    {
        $this->initTime = time();
    }

    public function addChars(?array $chars): void
    {
        if (!$chars) {
            return;
        }
        $this->chars ??= [];
        $length = count($chars);
        $j = 0;
        $jLen = count($this->chars);
        $found = false;

        for ($i = 0; $i < $length; ++$i) {
            $j = 0;
            $found = false;
            while ($j < $jLen) {
                if (
                    ($this->chars[$j]['style'] ?? null) === ($chars[$i]['style'] ?? null)
                    && ($this->chars[$j]['fFamily'] ?? null) === ($chars[$i]['fFamily'] ?? null)
                    && ($this->chars[$j]['ch'] ?? null) === ($chars[$i]['ch'] ?? null)
                ) {
                    $found = true;
                }
                ++$j;
            }
            if (!$found) {
                array_push($this->chars, $chars[$i]);
                ++$jLen;
            }
        }
    }

    public function addFonts(
        ?array $fontData,
        ?\DOMElement $defs
    ): void {
        if (!$fontData) {
            $this->isLoaded = true;

            return;
        }
        if ($this->chars) {
            $this->isLoaded = true;
            $this->fonts = $fontData['list'];

            return;
        }
        $length = count($fontData['list']);

        $this->isLoaded = true;

        for ($i = 0; $i < $length; ++$i) {
            $fontList = $fontData['list'][$i];

            if (!$fontList) {
                continue;
            }

            $fontList['helper'] = $this->createHelper($fontList);
            $fontList['cache'] = [];
        }
        $this->fonts = $fontData['list'];

        return;
        $_pendingFonts = $length;

        for ($i = 0; $i < $_pendingFonts; ++$i) {
            $shouldLoadFont = true;
            $loadedSelector = false;

            $fontList = $fontData['list'][$i];

            if (!$fontList) {
                continue;
            }

            $fontList['loaded'] = false;
            $fontList['monoCase'] = self::setUpNode(
                $fontList['fFamily'],
                'monospace'
            );
            $fontList['sansCase'] = self::setUpNode(
                $fontList['fFamily'],
                'sans-serif'
            );
            if (!$fontList['fPath']) {
                $fontList['loaded'] = true;
                --$_pendingFonts;
            } elseif (
                'p' === $fontList['fOrigin'] || 3 === $fontList['origin']
            ) {
                // $loadedSelector = document.querySelectorAll("style[f-forigin="p"][f-family="${$fontList.fFamily}"], style[f-origin="3"][f-family="${$fontList.fFamily}"]");

                // if (loadedSelector.length > 0) {
                //   shouldLoadFont = false;
                // }

                if ($shouldLoadFont) {
                    $s = domDocumentPool()->createElement('style');

                    $s->setAttribute('f-forigin', $fontList['fOrigin']);
                    $s->setAttribute('f-origin', $fontList['origin']);
                    $s->setAttribute('f-family', $fontList['fFamily']);
                    $s->textContent = "@font-face {font-family: {$fontList['fFamily']}; font-style: normal; src: url('{$fontList['fPath']}');}";
                    $defs?->appendChild($s);
                }
            } elseif (
                'g' === $fontList['fOrigin'] || 1 === $fontList['origin']
            ) {
                // $loadedSelector = domDocumentPool()->querySelectorAll('link[f-forigin="g"], link[f-origin="1"]');

                // $len = count($loadedSelector);

                // for ($i = 0; $i < $len; ++$i) {
                //     if (str_contains($loadedSelector[$i]?->getAttribute('href'), $fontList['fPath'])) {
                //         $shouldLoadFont = false;
                //     }
                // }

                if ($shouldLoadFont) {
                    $link = domDocumentPool()->createElement('link');

                    $link->setAttribute('f-forigin', $fontList['fOrigin']);
                    $link->setAttribute('f-origin', $fontList['origin']);
                    $link->setAttribute('type', 'text/css');
                    $link->setAttribute('rel', 'stylesheet');
                    $link->setAttribute('href', $fontList['fPath']);
                    domDocumentPool()->appendChild($link);
                }
            } elseif (
                't' === $fontList['fOrigin'] || 2 === $fontList['origin']
            ) {
                // $loadedSelector = domDocumentPool()->querySelectorAll('script[f-forigin="t"], script[f-origin="2"]');

                // $len = count($loadedSelector);

                // for ($i = 0; $i < $len; ++$i) {
                //     if ($fontList['fPath'] === $loadedSelector[$i]?->getAttribute('src')) {
                //         $shouldLoadFont = false;
                //     }
                // }

                if ($shouldLoadFont) {
                    $sc = domDocumentPool()->createElement('link');

                    $sc->setAttribute('f-forigin', $fontList['fOrigin']);
                    $sc->setAttribute('f-origin', $fontList['origin']);
                    $sc->setAttribute('rel', 'stylesheet');
                    $sc->setAttribute('href', $fontList['fPath']);
                    $defs?->appendChild($sc);
                }
            }
            $fontList['helper'] = $this->createHelper($fontList, $defs);
            $fontList['cache'] = [];
            array_push($this->fonts, $fontList);
        }
        if (0 === $_pendingFonts) {
            $this->isLoaded = true;
        }
    }

    public function getCharData(
        string $char,
        ?string $style,
        ?string $font
    ) {
        $i = 0;
        $length = count($this->chars ?? []);

        while ($i < $length) {
            if (
                ($this->chars[$i]['ch'] ?? null) === $char
                && ($this->chars[$i]['style'] ?? null) === $style
                && ($this->chars[$i]['fFamily'] ?? null) === $font
            ) {
                return $this->chars[$i];
            }
            ++$i;
        }
        if (
            ('string' === gettype($char) && 13 !== charCodeAt($char, 0) || !$char)
            && !$this->_warned
        ) {
            $this->_warned = true;
            error_log(
                "Missing character from exported characters list: \n"
        .$char."\n"
        .$style."\n"
        .$font
            );
        }

        return self::$emptyChar;
    }

    public function getFontByName(string $name): array
    {
        $i = 0;
        $length = count($this->fonts);

        while ($i < $length) {
            if (($this->fonts[$i]['fName'] ?? null) === $name) {
                return $this->fonts[$i];
            }
            ++$i;
        }

        return $this->fonts[0];
    }

    public static function isCombinedCharacter(int $char): bool
    {
        return in_array($char, self::$combinedCharacters);
    }

    public static function isFlagEmoji(string $string): bool
    {
        return
            self::isRegionalCode(substr($string, 0, 2))
            && self::isRegionalCode(substr($string, 2, 2));
    }

    public static function isModifier(
        int $firstCharCode,
        int $secondCharCode
    ): bool {
        $sum = dechex($firstCharCode) + dechex($secondCharCode);

        return in_array($sum, self::$surrogateModifiers);
    }

    public static function isRegionalCode(string $string): bool
    {
        $codePoint = self::getCodePoint($string);

        return
            $codePoint >= self::REGIONAL_CHARACTER_A_CODE_POINT
            && $codePoint <= self::REGIONAL_CHARACTER_Z_CODE_POINT;
    }

    public static function isRegionalFlag(string $text, int $indexFromProps): bool
    {
        $index = $indexFromProps;
        $codePoint = self::getCodePoint(substr($text, $index, 2));

        if (self::BLACK_FLAG_CODE_POINT !== $codePoint) {
            return false;
        }
        $count = 0;

        $index += 2;
        while ($count < 5) {
            $codePoint = self::getCodePoint(substr($text, $index, 2));
            if ($codePoint < self::A_TAG_CODE_POINT || $codePoint > self::Z_TAG_CODE_POINT) {
                return false;
            }
            ++$count;
            $index += 2;
        }

        return self::CANCEL_TAG_CODE_POINT === self::getCodePoint(substr($text, $index, 2));
    }

    public static function isVariationSelector(int $charCode): bool
    {
        return self::VARIATION_SELECTOR_16_CODE_POINT === $charCode;
    }

    public static function isZeroWidthJoiner(int $charCode): bool
    {
        return self::ZERO_WIDTH_JOINER_CODE_POINT === $charCode;
    }

    public function measureText(
        string $char,
        ?string $fontName,
        ?int $size
    ): int {
        $fontData = $this->getFontByName($fontName);
        $index = $char;

        if ($fontData['cache'] && !$fontData['cache'][$index]) {
            $tHelper = $fontData['helper'];

            if (' ' === $char) {
                $doubleSize = (float) $tHelper->measureText("|{$char}|");
                $singleSize = (float) $tHelper->measureText('||');

                $fontData['cache'][$index] = ($doubleSize - $singleSize) / 100;
            } else {
                $fontData['cache'][$index] = (float) $tHelper->measureText($char) / 100;
            }
        }

        return (float) ($fontData['cache'][$index] ?? 0) * (float) $size;
    }
    private $_warned = false;
    private static $combinedCharacters = [
        // Hindi characters
        2304,
        2305,
        2306,
        2307,
        2362,
        2363,
        2364,
        2364,
        2366,
        2367,
        2368,
        2369,
        2370,
        2371,
        2372,
        2373,
        2374,
        2375,
        2376,
        2377,
        2378,
        2379,
        2380,
        2381,
        2382,
        2383,
        2387,
        2388,
        2389,
        2390,
        2391,
        2402,
        2403,
    ];
    private static $emptyChar = [
        'data' => ['shapes' => []],
        'shapes' => [],
        'size' => 0,
        'w' => 0,
    ];
    private static ?RendererType $engine;

    private static ?\DOMElement $helper = null;
    private int $initTime;
    private $maxWaitingTime = 5000;
    private static $surrogateModifiers = [
        'd83cdffb',
        'd83cdffc',
        'd83cdffd',
        'd83cdffe',
        'd83cdfff',
    ];
    private const A_TAG_CODE_POINT = 917601;
    private const BLACK_FLAG_CODE_POINT = 127988;
    private const CANCEL_TAG_CODE_POINT = 917631;
    private const REGIONAL_CHARACTER_A_CODE_POINT = 127462;
    private const REGIONAL_CHARACTER_Z_CODE_POINT = 127487;
    private const VARIATION_SELECTOR_16_CODE_POINT = 65039;
    private const Z_TAG_CODE_POINT = 917626;
    private const ZERO_WIDTH_JOINER_CODE_POINT = 8205;
}
