<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\TestCase;

function replace_extension(string $filename, string $new_extension) {
    $info = pathinfo($filename);
    return ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '') 
        . $info['filename'] 
        . '.' 
        . $new_extension;
}

class LottieToSVGTest extends TestCase {
  public function test(): void {
    $testFile = 'loopOut-star.json';

    $generatedSVG = LottieToSVG::renderFirstFrame(dirname(__DIR__, 1) . "/assets/{$testFile}");

    $targetSVG = file_get_contents(dirname(__DIR__, 1) . "/assets/" . replace_extension($testFile, 'svg'));

    $this->assertEquals($targetSVG, $generatedSVG);
  }
}