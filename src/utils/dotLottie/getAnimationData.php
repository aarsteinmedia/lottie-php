<?php

namespace LottiePHP\Utils\DotLottie;

function getAnimationData(mixed $input)
{
    try {
        if (!$input || 'string' !== gettype($input) && 'object' !== gettype($input)) {
            throw new \Error('Broken file or invalid file format');
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

        /**
         * Check if file is JSON, first by parsing headers for content-type,
         * than by parsing filename, then – if filename has no extension – by
         * cloning the response and parsing response for content.
         */
        $isJSON = true;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $input);

        if (str_starts_with($mimeType, 'application/zip')) {
            $isJSON = false;
        }

        if ($isJSON) {
            $ext = pathinfo($input, PATHINFO_EXTENSION);

            if ('json' === $ext) {
                $lottie = $file;

                return [
                    'animations' => [$lottie],
                    'isDotLottie' => false,
                    'manifest' => null,
                ];
            }

            try {
                $lottie = json_decode($file);

                return [
                    'animations' => [$lottie],
                    'isDotLottie' => false,
                    'manifest' => null,
                ];
            } catch (\Exception $error) {
                // empty
            }
        }

        ['data' => $data, 'manifest' => $manifest] = getLottieJSON($file);

        return [
            'animations' => $data ?? [],
            'isDotLottie' => true,
            'manifest' => $manifest,
        ];
    } catch (\Exception $error) {
        echo $error->getMessage();

        return [
            'animations' => [],
            'isDotLottie' => false,
            'manifest' => null,
        ];
    }
}
