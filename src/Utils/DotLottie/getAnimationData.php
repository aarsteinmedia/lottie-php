<?php

namespace LottiePHP\Utils\DotLottie;

function getAnimationData(mixed $input)
{
    if (!$input || 'string' !== gettype($input) && 'object' !== gettype($input)) {
        throw new \InvalidArgumentException('Broken file or invalid file format');
    }

    if ('string' !== gettype($input)) {
        $animations = is_array($input) ? $input : [$input];

        return [
            'animations' => $animations,
            'isDotLottie' => false,
            'manifest' => null,
        ];
    }

    $file = file_get_contents($input);
    if (false === $file) {
        throw new \InvalidArgumentException('Could not read file: '.$input);
    }

    $isJSON = true;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $input);

    if (str_starts_with($mimeType, 'application/zip')) {
        $isJSON = false;
    }

    if ($isJSON) {
        $ext = pathinfo($input, PATHINFO_EXTENSION);

        if ('json' === $ext) {
            return [
                'animations' => [$file],
                'isDotLottie' => false,
                'manifest' => null,
            ];
        }

        $decoded = json_decode($file, true);
        if (is_array($decoded)) {
            return [
                'animations' => [$decoded],
                'isDotLottie' => false,
                'manifest' => null,
            ];
        }
    }

    ['data' => $data, 'manifest' => $manifest] = getLottieJSON($file);

    return [
        'animations' => $data ?? [],
        'isDotLottie' => true,
        'manifest' => $manifest,
    ];
}
