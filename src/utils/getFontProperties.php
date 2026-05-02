<?php

namespace LottiePHP\Utils;

function getFontProperties(array $fontData)
{
    $styles = $fontData['fStyle'] ? explode(' ', $fontData['fStyle']) : [];

    $fWeight = 'normal';
    $fStyle = 'normal';
    $length = count($styles);

    for ($i = 0; $i < $length; ++$i) {
        switch (strtolower($styles[$i] ?? '')) {
            case 'italic':
                $fStyle = 'italic';

                break;

            case 'bold':
                $fWeight = '700';

                break;

            case 'black':
                $fWeight = '900';

                break;

            case 'medium':
                $fWeight = '500';

                break;

            case 'semibold':
            case 'semi-bold':
            case 'demibold':
            case 'demi':
                $fWeight = '600';

                break;

            case 'extrabold':
            case 'extra-bold':
                $fWeight = '800';

                break;

            case 'ultralight':
            case 'ultra-light':
                $fWeight = '200';

                break;

            case 'regular':
            case 'normal':
                $fWeight = '400';

                break;

            case 'light':
            case 'thin':
                $fWeight = '200';

                break;

            default:
                break;
        }
    }

    return [
        'style' => $fStyle,
        'weight' => $fontData['fWeight'] ?? $fWeight,
    ];
}
