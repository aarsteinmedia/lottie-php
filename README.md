# lottie-php

Render still SVG frames from Lottie animations on the server — useful for previews, thumbnails, and CMS integrations without a browser runtime.

Supports **Lottie JSON** (`.json`) and **dotLottie** (`.lottie`) files.

## Requirements

- PHP 8.1+
- `ext-zip` (for `.lottie` archives)

## Installation

```bash
composer require aarsteinmedia/lottie-php
```

## Usage

Render a single frame to an SVG string:

```php
use LottiePHP\LottieToSVG;

$svg = LottieToSVG::renderFrame('/path/to/animation.json');
```

By default, the **middle frame** is rendered. Pass a frame index to pick another:

```php
$svg = LottieToSVG::renderFrame('/path/to/animation.json', frame: 0);
```

Optional renderer settings (size, aspect ratio, CSS class, etc.):

```php
$svg = LottieToSVG::renderFrame('/path/to/animation.lottie', [
    'width' => 512,
    'height' => 512,
    'preserveAspectRatio' => 'xMidYMid meet',
], frame: 0);
```

### Batch rendering

When rendering many frames from the same file, load once and reuse the animation instance:

```php
$anim = LottieToSVG::loadCached('/path/to/animation.json');

foreach ([0, 15, 30] as $frame) {
    $svg = LottieToSVG::renderFrameFromAnimation($anim, $frame);
}
```

`loadCached()` skips re-parsing while the file (path, modification time and size) and renderer settings are unchanged. `renderFrame()` uses it automatically.

The cache keeps the **most recently used** animation only, since a parsed animation can take tens of MB. Adjust it for your workload:

```php
LottieToSVG::setCacheSize(4); // keep up to 4 animations (least recently used is evicted)
LottieToSVG::setCacheSize(0); // disable caching, e.g. for one-off thumbnail jobs
LottieToSVG::clearCache();    // free everything now
```

## Development

Clone the repo, run `composer install`, then start the visual smoke-test UI:

```bash
composer visual:up
```

Open [http://localhost:8000](http://localhost:8000) to browse sample animations and inspect rendered SVG output.

Other scripts: `composer lint`, `composer visual:down`, `composer visual:logs`.
