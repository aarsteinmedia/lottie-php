<?php

namespace LottiePHP\Utils\Pooling;

function domDocumentPool(): \DOMDocument
{
    static $dom = null;
    if (null === $dom) {
        $dom = new \DOMDocument('1.0', 'UTF-8');
    }

    return $dom;
}
