<?php

namespace LottiePHP\Tests;

use LottiePHP\LottieToSVG;
use PHPUnit\Framework\TestCase;

class LottieToSVGTest extends TestCase {
  public function test_hello_world(): void {
    $hello_world = new LottieToSVG();
    $result = $hello_world->hello_world(['hello', 'world']);
    $this->assertEquals(['HELLO', 'WORLD'], $result);
  }
}