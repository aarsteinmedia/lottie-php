<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public const ASSETS_DIR = __DIR__.'/../assets';
    public const FIXTURES_DIR = __DIR__.'/fixtures';

    /** @var list<string> */
    private array $tempFiles = [];

    protected static function assertWellFormedSVG(string $svg): \DOMDocument
    {
        $doc = new \DOMDocument();
        self::assertTrue(@$doc->loadXML($svg), 'Output is not well-formed XML');
        self::assertSame('svg', $doc->documentElement?->localName);

        return $doc;
    }

    protected static function fixture(string $name): string
    {
        return self::FIXTURES_DIR.'/'.$name;
    }

    /** @return array<string, mixed> */
    protected static function fixtureJSON(string $name): array
    {
        return json_decode(file_get_contents(self::fixture($name)), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Element ids come from a process-wide counter, so renumber them by first appearance
     * to make output independent of what was rendered before.
     */
    protected static function normalizeIds(string $svg): string
    {
        $ids = [];

        return preg_replace_callback(
            '/__lottie_element_\d+/',
            static function (array $match) use (&$ids): string {
                return $ids[$match[0]] ??= '__lottie_element_'.(count($ids) + 1);
            },
            $svg
        );
    }

    /**
     * Reduce an SVG to what is actually visible, for comparing renders reached by different paths:
     * drops display:none subtrees and <defs> entries nothing visible references (e.g. masks of
     * out-of-range layers, which keep whatever an earlier frame left in them), renumbers ids and
     * rounds numbers to 3 decimals (interpolation caches can differ in the last float digits).
     */
    protected static function normalizeVisible(string $svg): string
    {
        $doc = self::assertWellFormedSVG($svg);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        foreach (iterator_to_array($xpath->query('//*[contains(@style, "display:none")]')) as $node) {
            $node->parentNode?->removeChild($node);
        }

        // Repeat, since removing one definition can orphan another it referenced
        do {
            $removed = 0;
            $xml = $doc->saveXML();
            foreach (iterator_to_array($xpath->query('//svg:defs/*[@id]')) as $node) {
                $id = $node->getAttribute('id');
                if (!str_contains($xml, "#{$id})") && !str_contains($xml, "\"#{$id}\"")) {
                    $node->parentNode->removeChild($node);
                    ++$removed;
                }
            }
        } while ($removed);

        return preg_replace_callback(
            '/-?\d+\.\d+(?:e-?\d+)?/i',
            static fn (array $match): string => (string) (round((float) $match[0], 3) + 0),
            self::normalizeIds($doc->saveXML($doc->documentElement))
        );
    }

    protected function setUp(): void
    {
        LottieToSVG::setCacheSize(1);
        LottieToSVG::clearCache();
    }

    protected function tearDown(): void
    {
        LottieToSVG::setCacheSize(1);
        LottieToSVG::clearCache();

        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    /**
     * Build a .lottie archive in a temp file.
     *
     * @param array<string, string> $entries archive path => contents
     */
    protected function tempDotLottie(array $entries): string
    {
        $path = $this->tempPath('lottie');

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return $path;
    }

    protected function tempFile(string $contents, string $extension): string
    {
        $path = $this->tempPath($extension);
        file_put_contents($path, $contents);

        return $path;
    }

    protected function tempPath(string $extension): string
    {
        $path = sys_get_temp_dir().'/lottie-test-'.bin2hex(random_bytes(8)).'.'.$extension;
        $this->tempFiles[] = $path;

        return $path;
    }
}
