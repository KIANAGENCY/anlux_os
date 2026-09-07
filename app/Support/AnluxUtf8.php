<?php

declare(strict_types=1);

namespace App\Support;

final class AnluxUtf8
{
    public static function fromCodepoint(int $codepoint): string
    {
        if (function_exists('mb_chr')) {
            $char = mb_chr($codepoint, 'UTF-8');
            if (is_string($char) && $char !== '') {
                return $char;
            }
        }

        return html_entity_decode('&#'.$codepoint.';', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
