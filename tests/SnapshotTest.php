<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Renders the default frame of every file in assets/ and compares it with a stored hash.
 *
 * Full SVGs would add ~30 MB to the repo, so only SHA-1s are kept in snapshots.json.
 * On a mismatch the actual SVG is written to tests/__output__/ for inspection.
 * After an intended rendering change, run `composer test:update-snapshots`.
 *
 * @internal
 *
 * @coversNothing
 */
final class SnapshotTest extends TestCase
{
    /** @return array<string, string> */
    private static function loadSnapshots(): array
    {
        if (!is_file(self::SNAPSHOTS)) {
            return [];
        }

        return json_decode(file_get_contents(self::SNAPSHOTS), true, 512, JSON_THROW_ON_ERROR);
    }

    private static function shouldUpdate(): bool
    {
        return (bool) getenv('UPDATE_SNAPSHOTS');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::shouldUpdate() && null !== self::$snapshots) {
            // Drop snapshots of assets that no longer exist
            self::$snapshots = array_intersect_key(
                self::$snapshots,
                iterator_to_array(self::assets())
            );
            ksort(self::$snapshots);
            file_put_contents(
                self::SNAPSHOTS,
                json_encode(self::$snapshots, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );
        }
    }

    #[DataProvider('assets')]
    public function testRendersAssetAsBefore(string $path): void
    {
        $name = basename($path);
        $svg = LottieToSVG::renderFrame($path);

        self::assertIsString($svg);
        self::assertWellFormedSVG($svg);

        $hash = sha1(self::normalizeIds($svg));
        self::$snapshots ??= self::loadSnapshots();

        if (self::shouldUpdate()) {
            self::$snapshots[$name] = $hash;

            return;
        }

        self::assertArrayHasKey(
            $name,
            self::$snapshots,
            "No snapshot for {$name}. Run `composer test:update-snapshots` to record it."
        );

        if (self::$snapshots[$name] !== $hash) {
            @mkdir(self::OUTPUT_DIR, 0o777, true);
            file_put_contents(self::OUTPUT_DIR."/{$name}.svg", $svg);
        }

        self::assertSame(
            self::$snapshots[$name],
            $hash,
            "Rendered SVG for {$name} changed; actual output written to tests/__output__/{$name}.svg"
        );
    }

    /** @return iterable<string, array{string}> */
    public static function assets(): iterable
    {
        foreach (glob(self::ASSETS_DIR.'/*.{json,lottie}', GLOB_BRACE) as $path) {
            yield basename($path) => [$path];
        }
    }

    /** @var null|array<string, string> */
    private static ?array $snapshots = null;
    private const OUTPUT_DIR = __DIR__.'/__output__';
    private const SNAPSHOTS = __DIR__.'/snapshots.json';
}
