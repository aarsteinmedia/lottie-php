<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 *
 * @coversNothing
 */
final class LottieToSVGTest extends TestCase
{
    public function testAppliesRendererSettings(): void
    {
        $svg = LottieToSVG::renderFrame(self::fixture('moving-square.json'), [
            'width' => 512,
            'height' => 256,
            'preserveAspectRatio' => 'none',
            'className' => 'preview',
        ]);
        $root = self::assertWellFormedSVG($svg)->documentElement;

        self::assertSame('512', $root->getAttribute('width'));
        self::assertSame('256', $root->getAttribute('height'));
        self::assertSame('none', $root->getAttribute('preserveAspectRatio'));
        self::assertSame('preview', $root->getAttribute('class'));
    }

    public function testIsLottie(): void
    {
        self::assertTrue(LottieToSVG::isLottie(self::fixtureJSON('moving-square.json')));
        self::assertFalse(LottieToSVG::isLottie([]));

        foreach (['v', 'ip', 'op', 'layers', 'fr', 'w', 'h'] as $key) {
            $json = self::fixtureJSON('moving-square.json');
            unset($json[$key]);
            self::assertFalse(LottieToSVG::isLottie($json), "Accepted Lottie without '{$key}'");
        }
    }

    public function testLoadsJSONWithoutJSONExtension(): void
    {
        $path = $this->tempFile(file_get_contents(self::fixture('moving-square.json')), 'txt');

        self::assertStringContainsString('<path', LottieToSVG::renderFrame($path));
    }

    public function testRejectsInvalidJSON(): void
    {
        $path = $this->tempFile('{"v": "5.7.4", ', 'json');

        $this->expectException(\InvalidArgumentException::class);
        LottieToSVG::load($path);
    }

    public function testRejectsJSONThatIsNotLottie(): void
    {
        $path = $this->tempFile('{"hello": "world"}', 'json');

        $this->expectException(\InvalidArgumentException::class);
        LottieToSVG::load($path);
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(\Throwable::class);
        @LottieToSVG::load(self::FIXTURES_DIR.'/does-not-exist.json');
    }

    public function testRenderFrameFromAnimationRendersManyFrames(): void
    {
        $anim = LottieToSVG::load(self::fixture('moving-square.json'));

        self::assertStringContainsString(',10,50)', LottieToSVG::renderFrameFromAnimation($anim, 0));
        self::assertStringContainsString(',90,50)', LottieToSVG::renderFrameFromAnimation($anim, 59));
        self::assertStringContainsString(',10,50)', LottieToSVG::renderFrameFromAnimation($anim, 0));
    }

    public function testRendersExpectedStructure(): void
    {
        $doc = self::assertWellFormedSVG(LottieToSVG::renderFrame(self::fixture('moving-square.json'), frame: 0));
        $svg = $doc->documentElement;

        self::assertSame('0 0 100 100', $svg->getAttribute('viewBox'));
        self::assertSame('xMidYMid meet', $svg->getAttribute('preserveAspectRatio'));

        $paths = $doc->getElementsByTagName('path');
        self::assertCount(1, $paths);
        self::assertSame('rgb(255,0,0)', $paths->item(0)->getAttribute('fill'));
    }

    public function testRendersMiddleFrameByDefault(): void
    {
        $default = LottieToSVG::renderFrame(self::fixture('moving-square.json'));
        $middle = LottieToSVG::renderFrame(self::fixture('moving-square.json'), frame: 30, force: true);

        self::assertSame($middle, $default);
    }

    #[DataProvider('frames')]
    public function testRendersRequestedFrame(int $frame, string $transform): void
    {
        $svg = LottieToSVG::renderFrame(self::fixture('moving-square.json'), frame: $frame);

        self::assertStringContainsString($transform, $svg);
    }

    /** @return iterable<string, array{int, string}> */
    public static function frames(): iterable
    {
        // The square moves linearly from x=10 to x=90 over frames 0–59
        yield 'first frame' => [0, 'matrix(1,0,0,1,10,50)'];

        yield 'middle frame' => [30, 'matrix(1,0,0,1,50.677966101695,50)'];

        yield 'last frame' => [59, 'matrix(1,0,0,1,90,50)'];

        yield 'past the end is clamped' => [500, 'matrix(1,0,0,1,90,50)'];
    }
}
