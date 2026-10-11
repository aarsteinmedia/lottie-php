<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;

use function LottiePHP\Utils\DotLottie\unzipToMap;

/**
 * @internal
 *
 * @coversNothing
 */
final class DotLottieTest extends TestCase
{
    public function testEmbedsImageAssets(): void
    {
        $json = self::fixtureJSON('moving-square.json');
        $json['assets'] = [['id' => 'img_0', 'w' => 1, 'h' => 1, 'u' => '/images/', 'p' => 'dot.png', 'e' => 0]];
        $json['layers'][] = [
            'ty' => 2, 'ind' => 2, 'refId' => 'img_0', 'ip' => 0, 'op' => 60, 'st' => 0, 'sr' => 1,
            'ks' => [
                'o' => ['a' => 0, 'k' => 100], 'r' => ['a' => 0, 'k' => 0], 'p' => ['a' => 0, 'k' => [0, 0, 0]],
                'a' => ['a' => 0, 'k' => [0, 0, 0]], 's' => ['a' => 0, 'k' => [100, 100, 100]],
            ],
        ];
        $path = $this->tempDotLottie([
            'manifest.json' => json_encode(['version' => '1', 'animations' => [['id' => 'square']]]),
            'animations/square.json' => json_encode($json),
            'images/dot.png' => base64_decode(self::PNG),
        ]);

        $images = self::assertWellFormedSVG(LottieToSVG::renderFrame($path))->getElementsByTagName('image');

        self::assertCount(1, $images);
        self::assertSame('data:image/png;base64,'.self::PNG, $images->item(0)->getAttribute('href'));
    }

    public function testIsSafeZip(): void
    {
        $zip = new \ZipArchive();
        $zip->open($this->tempDotLottie(['manifest.json' => '{}', 'a/x.json' => '{}']));
        self::assertTrue(LottieToSVG::isSafeZip($zip));
        $zip->close();
    }

    public function testLoadsVersion1Layout(): void
    {
        $path = $this->tempDotLottie([
            'manifest.json' => json_encode(['version' => '1', 'animations' => [['id' => 'square']]]),
            'animations/square.json' => file_get_contents(self::fixture('moving-square.json')),
        ]);

        self::assertStringContainsString('matrix(1,0,0,1,10,50)', LottieToSVG::renderFrame($path, frame: 0));
    }

    public function testLoadsVersion2Layout(): void
    {
        $path = $this->tempDotLottie([
            'manifest.json' => json_encode(['version' => '2', 'animations' => [['id' => 'square']]]),
            'a/square.json' => file_get_contents(self::fixture('moving-square.json')),
        ]);

        self::assertStringContainsString('matrix(1,0,0,1,90,50)', LottieToSVG::renderFrame($path, frame: 59));
    }

    public function testOnlyExtractsKnownFolders(): void
    {
        $path = $this->tempDotLottie([
            'manifest.json' => '{}',
            'a/anim.json' => '{}',
            'animations/anim.json' => '{}',
            'i/img.png' => 'png',
            'images/img.png' => 'png',
            'u/sound.mp3' => 'mp3',
            'audio/sound.mp3' => 'mp3',
            'evil/payload.bin' => 'x',
            'payload.bin' => 'x',
            'nested/a/anim.json' => '{}',
        ]);

        $extracted = array_keys(unzipToMap(file_get_contents($path)));
        sort($extracted);

        self::assertSame([
            'a/anim.json',
            'animations/anim.json',
            'audio/sound.mp3',
            'i/img.png',
            'images/img.png',
            'manifest.json',
            'u/sound.mp3',
        ], $extracted);
    }

    public function testRejectsHighCompressionRatio(): void
    {
        $path = $this->tempDotLottie([
            'manifest.json' => json_encode(['animations' => [['id' => 'bomb']]]),
            'a/bomb.json' => str_repeat('0', 2_000_000),
        ]);

        $this->expectException(\RuntimeException::class);
        LottieToSVG::load($path);
    }

    public function testRejectsManifestWithoutAnimationData(): void
    {
        $path = $this->tempDotLottie([
            'manifest.json' => json_encode(['animations' => [['id' => 'missing']]]),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        LottieToSVG::load($path);
    }

    public function testRejectsMissingManifest(): void
    {
        $path = $this->tempDotLottie([
            'a/square.json' => file_get_contents(self::fixture('moving-square.json')),
        ]);

        $this->expectException(\RuntimeException::class);
        LottieToSVG::load($path);
    }

    public function testRejectsTooManyEntries(): void
    {
        $entries = ['manifest.json' => json_encode(['animations' => [['id' => 'x']]])];
        for ($i = 0; $i <= 1000; ++$i) {
            $entries["i/{$i}.png"] = (string) $i;
        }
        $path = $this->tempDotLottie($entries);

        $this->expectException(\RuntimeException::class);
        LottieToSVG::load($path);
    }
    // 1×1 transparent PNG
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
}
