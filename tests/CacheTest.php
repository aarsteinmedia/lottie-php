<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;

use function LottiePHP\Utils\Pooling\domDocumentPool;

/**
 * @internal
 *
 * @coversNothing
 */
final class CacheTest extends TestCase
{
    public function testCachedRenderIsIdentical(): void
    {
        $first = LottieToSVG::renderFrame($this->a, frame: 0);

        self::assertSame($first, LottieToSVG::renderFrame($this->a, frame: 0, force: true));
    }

    public function testClearCache(): void
    {
        $a = LottieToSVG::loadCached($this->a);
        LottieToSVG::clearCache();

        self::assertNotSame($a, LottieToSVG::loadCached($this->a));
    }

    public function testDefaultSizeKeepsOnlyTheLatestAnimation(): void
    {
        $a = LottieToSVG::loadCached($this->a);
        LottieToSVG::loadCached($this->b);

        self::assertNotSame($a, LottieToSVG::loadCached($this->a));
    }

    public function testEvictsLeastRecentlyUsed(): void
    {
        LottieToSVG::setCacheSize(2);
        $a = LottieToSVG::loadCached($this->a);
        $b = LottieToSVG::loadCached($this->b);

        LottieToSVG::loadCached($this->a); // a is now most recently used
        LottieToSVG::loadCached(self::fixture('moving-square.json')); // evicts b

        self::assertSame($a, LottieToSVG::loadCached($this->a));
        self::assertNotSame($b, LottieToSVG::loadCached($this->b));
    }

    public function testRejectsNegativeSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LottieToSVG::setCacheSize(-1);
    }

    public function testReleasedAnimationsAreFreed(): void
    {
        LottieToSVG::setCacheSize(0);
        $path = self::ASSETS_DIR.'/1729.json'; // ~25 MB once parsed

        LottieToSVG::renderFrame($path);
        LottieToSVG::clearCache();
        $baseline = memory_get_usage();

        for ($i = 0; $i < 5; ++$i) {
            LottieToSVG::renderFrame($path);
        }
        LottieToSVG::clearCache();

        self::assertLessThan(2_000_000, memory_get_usage() - $baseline, 'Memory grows across renders');
    }

    public function testReloadsModifiedFile(): void
    {
        $before = LottieToSVG::loadCached($this->a);

        // Same mtime second is likely here; the size change must still invalidate the entry
        $json = self::fixtureJSON('moving-square.json');
        $json['w'] = 1000;
        file_put_contents($this->a, json_encode($json));

        $after = LottieToSVG::loadCached($this->a);
        self::assertNotSame($before, $after);
        self::assertSame(1000, $after->animationData['w']);
    }

    public function testRendererSettingsArePartOfTheKey(): void
    {
        LottieToSVG::setCacheSize(2);
        $plain = LottieToSVG::loadCached($this->a);
        $sized = LottieToSVG::loadCached($this->a, ['width' => 100]);

        self::assertNotSame($plain, $sized);
        self::assertSame($plain, LottieToSVG::loadCached($this->a));
        self::assertSame($sized, LottieToSVG::loadCached($this->a, ['width' => 100]));
        self::assertStringContainsString('width="100"', LottieToSVG::renderFrame($this->a, ['width' => 100]));
    }

    public function testRenderingLeavesNothingInSharedDocument(): void
    {
        LottieToSVG::setCacheSize(0);
        for ($i = 0; $i < 5; ++$i) {
            LottieToSVG::renderFrame($this->a);
        }

        self::assertSame(0, domDocumentPool()->childNodes->length);
    }

    public function testReusesCachedAnimation(): void
    {
        self::assertSame(LottieToSVG::loadCached($this->a), LottieToSVG::loadCached($this->a));
    }

    public function testShrinkingEvictsEntries(): void
    {
        $a = LottieToSVG::loadCached($this->a);
        LottieToSVG::setCacheSize(0);
        LottieToSVG::setCacheSize(1);

        self::assertNotSame($a, LottieToSVG::loadCached($this->a));
    }

    public function testSizeZeroDisablesCaching(): void
    {
        LottieToSVG::setCacheSize(0);

        self::assertNotSame(LottieToSVG::loadCached($this->a), LottieToSVG::loadCached($this->a));
    }
    private string $a;
    private string $b;

    protected function setUp(): void
    {
        parent::setUp();

        $json = file_get_contents(self::fixture('moving-square.json'));
        $this->a = $this->tempFile($json, 'json');
        $this->b = $this->tempFile($json, 'json');
    }
}
