<?php

namespace LottiePHP\Utils;

class FontManager
{
    public ?array $chars = null;
    public array $fonts = [];
    public bool $isLoaded = false;
    public int $typekitLoaded = 0;

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
            $this->checkLoadedFontsBound();

            return;
        }
        sleep(0.01);
        $this->setIsLoadedBound();
    }

    private function checkLoadedFontsBound(...$args)
    {
        return $this->checkLoadedFonts(...$args);
    }

    private function createHelper(
        array $fontData,
        ?\DOMElement $def = null
    ) {
        $fontProps = getFontProperties($fontData);

        $tHelper = General::createTag('text');

        General::addStyles($tHelper, ['font-size' => '100px']);

        $tHelper->setAttribute('font-family', $fontData['fFamily']);
        $tHelper->setAttribute('font-style', $fontProps['style']);
        $tHelper->setAttribute('font-weight', $fontProps['weight']);
        $tHelper->textContent = '1';
        if ($fontData['fClass'] ?? null) {
            General::addStyles($tHelper, ['font-family' => 'inherit']);
            General::addClasses($tHelper, [$fontData['fClass']]);
        } else {
            General::addStyles($tHelper, ['font-family' => $fontData['fFamily']]);
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
        if ('' === $string) {
            return 0;
        }

        if (function_exists('mb_ord')) {
            return mb_ord($string, 'UTF-8') ?? 0;
        }

        $first = General::charCodeAt($string, 0);

        if ($first >= 0xD800 && $first <= 0xDBFF) {
            $second = General::charCodeAt($string, 1);

            if ($second >= 0xDC00 && $second <= 0xDFFF) {
                return ($first - 0xD800) * 0x400 + $second - 0xDC00 + 0x10000;
            }
        }

        return $first;
    }

    /**
     * @param array<string, mixed> $char
     *
     * @return array<string, mixed>
     */
    private function normalizeCharData(array $char): array
    {
        if (!isset($char['data']['shapes']) || !is_array($char['data']['shapes'])) {
            return $char;
        }

        $char['data']['shapes'] = $this->normalizeShapeTree($char['data']['shapes']);

        return $char;
    }

    /**
     * @param array<int, array<string, mixed>> $shapes
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeShapeTree(array $shapes): array
    {
        foreach ($shapes as $shapeIndex => $shape) {
            if (($shape['ty'] ?? '') === 'gr' && isset($shape['it']) && is_array($shape['it'])) {
                foreach ($shape['it'] as $itemIndex => $item) {
                    if (
                        ($item['ty'] ?? '') === 'sh'
                        && isset($item['ks']['k'])
                        && is_array($item['ks']['k'])
                    ) {
                        $shapes[$shapeIndex]['it'][$itemIndex]['ks']['k']
                            = normalizePathDataTangents($item['ks']['k']);
                    }
                }
            }

            if (
                ($shape['ty'] ?? '') === 'sh'
                && isset($shape['ks']['k'])
                && is_array($shape['ks']['k'])
            ) {
                $shapes[$shapeIndex]['ks']['k'] = normalizePathDataTangents($shape['ks']['k']);
            }
        }

        return $shapes;
    }

    private function setIsLoaded(): void
    {
        $this->isLoaded = true;
    }

    private function setIsLoadedBound(...$args)
    {
        return $this->setIsLoaded(...$args);
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
                array_push($this->chars, $this->normalizeCharData($chars[$i]));
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
            ('string' === gettype($char) && 13 !== General::charCodeAt($char, 0) || !$char)
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

        if ($fontData['cache'] ?? null && !$fontData['cache'][$index]) {
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
    private static array $combinedCharacters = [
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
