<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use LottiePHP\LottieToSVG;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isAllowedAsset(string $path): bool
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return in_array($ext, ['json', 'lottie'], true);
}

$root = dirname(__DIR__, 2);
$assetsDir = $root.'/assets';

$assets = [];
if (is_dir($assetsDir)) {
    $files = scandir($assetsDir) ?: [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (!isAllowedAsset($file)) {
            continue;
        }
        $assets[] = $file;
    }
}
sort($assets);

$file = $_GET['file'] ?? ($assets[0] ?? null);
$selected = is_string($file) ? basename($file) : null;
$selectedPath = $selected ? ($assetsDir.'/'.$selected) : null;

$svg = null;
$error = null;
if ($selectedPath && file_exists($selectedPath) && isAllowedAsset($selectedPath)) {
    try {
        $svg = LottieToSVG::renderFirstFrame($selectedPath);
        if (!$svg) {
            $error = 'Renderer returned no SVG output.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
} elseif ($selected !== null) {
    $error = 'Asset not found.';
}

?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>lottie-php visual</title>
    <link rel="stylesheet" href="/style.css" />
  </head>
  <body>
    <header class="header">
      <div class="container">
        <div class="title">lottie-php visual</div>
        <div class="subtitle">First-frame render to SVG (quick visual smoke test)</div>
      </div>
    </header>

    <main class="container layout">
      <aside class="panel">
        <form method="get" class="form">
          <label class="label" for="file">Asset</label>
          <select autofocus class="select" name="file" id="file" onchange="this.form.submit()">
            <?php foreach ($assets as $asset): ?>
              <option value="<?= h($asset) ?>" <?= $asset === $selected ? 'selected' : '' ?>>
                <?= h($asset) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="button" type="submit">Load</button></noscript>
        </form>

        <div class="meta">
          <div><span class="k">Path</span> <span class="v"><?= $selected ? h('assets/'.$selected) : '—' ?></span></div>
          <div><span class="k">PHP</span> <span class="v"><?= h(PHP_VERSION) ?></span></div>
        </div>
      </aside>

      <section class="stage">
        <?php if ($error): ?>
          <div class="error"><?= h($error) ?></div>
        <?php elseif ($svg): ?>
          <div class="frame">
            <?= $svg ?>
          </div>
        <?php else: ?>
          <div class="empty">No assets found in <code>assets/</code>.</div>
        <?php endif; ?>
      </section>
    </main>
  </body>
</html>
