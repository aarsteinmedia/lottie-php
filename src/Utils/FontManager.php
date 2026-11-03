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

    /** Advance width in em */
    private static function estimateCharWidth(string $char, string $family, bool $bold): float
    {
        $code = self::getCodePoint($char);

        if (preg_match('/mono|courier|consolas|menlo|typewriter/', $family)) {
            return 0.6;
        }
        if ($code >= 0x2E80 && $code <= 0xFFEF) {
            return 1.0;
        }

        $serif = (bool) preg_match('/times|georgia|garamond|palatino|cambria|script|(^|[^s])serif/', $family)
            && !preg_match('/sans/', $family);
        $widths = $serif ? self::TIMES_WIDTHS : self::HELVETICA_WIDTHS;
        $width = ($code >= 32 && $code <= 126 ? $widths[$code - 32] : ($serif ? 500 : 556)) / 1000;

        return $bold ? $width * 1.06 : $width;
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

    /**
     * Estimate the advance width of a character at the given font size.
     *
     * There is no font rasteriser on the server (lottie-web asks the browser to measure), so this
     * uses the standard Helvetica/Times metrics, or a fixed advance for monospace families.
     */
    public function measureText(
        string $char,
        ?string $fontName,
        ?int $size
    ): float {
        if ('' === $char) {
            return 0.0;
        }
        $fontData = [] === $this->fonts ? [] : $this->getFontByName((string) $fontName);
        $family = strtolower((string) ($fontData['fFamily'] ?? $fontName ?? ''));
        $bold = (bool) preg_match('/bold|black|heavy|[6-9]00/i', ($fontData['fStyle'] ?? '').' '.($fontData['fWeight'] ?? ''));

        $key = $family.'|'.($bold ? 'b' : 'n').'|'.$char;
        if (!isset($this->widthCache[$key])) {
            $this->widthCache[$key] = self::estimateCharWidth($char, $family, $bold);
        }

        return $this->widthCache[$key] * (float) $size;
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

    /** @var array<string, float> */
    private array $widthCache = [];
    private const A_TAG_CODE_POINT = 917601;
    private const BLACK_FLAG_CODE_POINT = 127988;
    private const CANCEL_TAG_CODE_POINT = 917631;

    // Advance widths of printable ASCII (32-126) in 1/1000 em
    private const HELVETICA_WIDTHS = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];
    private const REGIONAL_CHARACTER_A_CODE_POINT = 127462;
    private const REGIONAL_CHARACTER_Z_CODE_POINT = 127487;
    private const TIMES_WIDTHS = [
        250, 333, 408, 500, 500, 833, 778, 180, 333, 333, 500, 564, 250, 333, 250, 278,
        500, 500, 500, 500, 500, 500, 500, 500, 500, 500, 278, 278, 564, 564, 564, 444,
        921, 722, 667, 667, 722, 611, 556, 722, 722, 333, 389, 722, 611, 889, 722, 722,
        556, 722, 667, 556, 611, 722, 722, 944, 722, 722, 611, 333, 278, 333, 469, 500,
        333, 444, 500, 444, 500, 444, 333, 500, 500, 278, 278, 500, 278, 778, 500, 500,
        500, 500, 333, 389, 278, 500, 500, 722, 500, 500, 444, 480, 200, 480, 541,
    ];
    private const VARIATION_SELECTOR_16_CODE_POINT = 65039;
    private const Z_TAG_CODE_POINT = 917626;
    private const ZERO_WIDTH_JOINER_CODE_POINT = 8205;
}
