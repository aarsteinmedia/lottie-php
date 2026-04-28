<?php

namespace LottiePHP\Utils\DotLottie;

/**
 * @param array<string, string> $unzipped
 * @param array<string, mixed>  $lottie
 */
function resolveAssets(array $unzipped, array $lottie): void
{
    $assets = $lottie['assets'] ?? [];
    if (!is_array($assets)) {
        return;
    }
    foreach ($assets as $i => $asset) {
        if (!is_array($asset)) {
            continue;
        }
        if (!isAudioAsset($asset) && !isImageAsset($asset)) {
            continue;
        }
        $isImage = isImageAsset($asset);
        $type = $isImage ? 'images' : 'audio';
        $u = isset($asset['u']) ? preg_replace('/^\/+/', '', (string) $asset['u']) : '';
        $p = isset($asset['p']) ? (string) $asset['p'] : '';
        $raw = null;
        if ('' !== $u) {
            $raw = $unzipped[$u.$p] ?? null;
        } elseif ($isImage) {
            $raw = $unzipped['i/'.$p] ?? null;
        }
        $raw ??= $unzipped["{$type}/{$p}"] ?? null;
        if (null === $raw || '' === $raw) {
            continue;
        }
        $b64 = base64_encode($raw);
        $currentP = $assets[$i]['p'] ?? '';
        if (!is_string($currentP)
            || '' === $currentP
            || str_starts_with($currentP, 'data:')
            || looksLikeBase64($currentP)) {
            // keep p
        } else {
            $assets[$i]['p'] = 'data:'.getMimeFromExt(getExt($p)).';base64,'.$b64;
        }
        $assets[$i]['e'] = 1;
        $assets[$i]['u'] = '';
    }
}

function looksLikeBase64(string $str): bool
{
    return (bool) preg_match(
        '/^(?:[0-9a-z+\/]{4})*(?:[0-9a-z+\/]{2}==|[0-9a-z+\/]{3}=)?$/i',
        parseBase64Payload($str)
    );
}

function isAudioAsset(array $asset): bool
{
    return !array_key_exists('h', $asset)
        && !array_key_exists('w', $asset)
        && isset($asset['p'], $asset['e'], $asset['u'], $asset['id']);
}
function isImageAsset(array $asset): bool
{
    return isset($asset['w'], $asset['h'], $asset['p']) && !isset($asset['xt']);
}

function parseBase64Payload(string $str): string
{
    $comma = strpos($str, ',');

    return false === $comma ? $str : substr($str, (int) $comma + 1);
}

function getExt(?string $path): ?string
{
    if (null === $path || '' === $path) {
        return null;
    }
    $base = basename($path);
    $dot = strrpos($base, '.');

    return (false === $dot || $dot < 1) ? null : strtolower(substr($base, $dot + 1));
}

function getMimeFromExt(?string $ext): string
{
    return match ($ext) {
        'svg', 'svg+xml' => 'image/svg+xml',
        'jpg', 'jpeg' => 'image/jpeg',
        'png', 'gif', 'webp', 'avif' => "image/{$ext}",
        'mp3', 'mpeg', 'wav' => "audio/{$ext}",
        default => '',
    };
}
