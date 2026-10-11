<?php

namespace LottiePHP\Tests;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Autoloads every class, trait and interface under src/. Missing abstract methods,
 * misnamed files and unresolvable traits are fatal at compile time, so this runs in
 * its own process to report them as a failure instead of crashing the suite.
 *
 * @internal
 *
 * @coversNothing
 */
final class ClassLoadingTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testEverySourceFileAutoloads(): void
    {
        $src = realpath(__DIR__.'/../src');
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $functionFiles = array_map(
            static fn (string $file) => realpath(__DIR__.'/../'.$file),
            $composer['autoload']['files']
        );

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));
        $count = 0;

        foreach ($files as $file) {
            if ('php' !== $file->getExtension() || in_array($file->getRealPath(), $functionFiles, true)) {
                continue;
            }

            $name = 'LottiePHP\\'.str_replace('/', '\\', substr($file->getRealPath(), strlen($src) + 1, -4));

            self::assertTrue(
                class_exists($name) || trait_exists($name) || interface_exists($name) || enum_exists($name),
                "{$name} is not declared in its PSR-4 file"
            );
            ++$count;
        }

        self::assertGreaterThan(100, $count);
    }
}
