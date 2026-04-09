<?php

namespace LottiePHP;

class LottieToSVG {
  public function hello_world(array $data): array {
    return array_map(fn($item) => strtoupper($item), $data);
  }
}