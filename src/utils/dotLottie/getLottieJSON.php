<?php

namespace LottiePHP\Utils\DotLottie;

/**
 * @return array{data: list<array<string, mixed>>, manifest: array<string, mixed>}
 */
function getLottieJSON(string $zipBinary): array
{
    try {
        $unzipped = unzipToMap($zipBinary);
        $manifest = getManifest($unzipped);
        $data = [];
        $animationsFolder = 'animations';
        $firstId = $manifest['animations'][0]['id'] ?? null;
        if (($manifest['version'] ?? null) === '2'
            || (null !== $firstId && isset($unzipped["a/{$firstId}.json"]))) {
            $animationsFolder = 'a';
        }
        $length = count($manifest['animations'] ?? []);
        for ($i = 0; $i < $length; ++$i) {
            $animation = $manifest['animations'][$i];

            $id = $animation['id'] ?? null;
            if (null === $id) {
                continue;
            }
            $path = "{$animationsFolder}/{$id}.json";

            if (!isset($unzipped[$path])) {
                continue;
                // throw new \RuntimeException("Missing animation JSON: {$path}");
            }

            /** @var array<string, mixed> $lottie */
            $lottie = json_decode(prepareString($unzipped[$path]), true, 512, JSON_THROW_ON_ERROR);

            // decodeExpressionLayers($lottie);
            resolveAssets($unzipped, $lottie['assets'] ?? []);

            $data[] = $lottie;
        }

        return ['data' => $data, 'manifest' => $manifest];
    } catch (\Throwable $error) {
        echo $error->getMessage();

        return ['data' => null, 'manifest' => null];
    }
}

/** @return array<string, string> */
function unzipToMap(string $zipBinary): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'dlottie');
    if (false === $tmp) {
        throw new \RuntimeException('Could not create temp file for ZIP');
    }

    try {
        file_put_contents($tmp, $zipBinary);
        $zip = new \ZipArchive();
        if (true !== $zip->open($tmp)) {
            throw new \RuntimeException('Invalid or unreadable ZIP');
        }
        $map = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (false === $name || str_ends_with($name, '/')) {
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if (false !== $contents) {
                $map[$name] = $contents;
            }
        }
        $zip->close();

        return $map;
    } finally {
        @unlink($tmp);
    }
}

/** @param array<string, string> $unzipped */
function getManifest(array $unzipped): array
{
    if (!isset($unzipped['manifest.json'])) {
        throw new \RuntimeException('Manifest not found');
    }

    /** @var array<string, mixed> $manifest */
    $manifest = json_decode($unzipped['manifest.json'], true, 512, JSON_THROW_ON_ERROR);
    if (!isset($manifest['animations']) || !is_array($manifest['animations'])) {
        throw new \RuntimeException('Manifest not found');
    }
    if ([] === $manifest['animations']) {
        throw new \RuntimeException('No animations listed in manifest');
    }

    return $manifest;
}

function prepareString(string $str): string
{
    $str = iconv('UTF-8', 'UTF-8//IGNORE', str_replace('"""', '""', $str));

    return preg_replace_callback('/(["\'])(.*?)\1/s', static function (array $m): string {
        return $m[1].$m[2].$m[1];
    }, $str);
}
