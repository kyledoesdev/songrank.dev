<?php

namespace App\Actions;

use Blaspsoft\Blasp\Facades\Blasp;

final class MaskProfanity
{
    public function handle(string $text, ?string $keep = null): string
    {
        if (blank($keep)) {
            return Blasp::english()->mask('*')->check($text)->clean();
        }

        /* preg_split puts each kept name it splits on at an odd index. */
        return collect(preg_split('/('.preg_quote($keep, '/').')/iu', $text, flags: PREG_SPLIT_DELIM_CAPTURE))
            ->map(fn (string $part, int $index): string => $index % 2 === 1 ? $part : $this->handle($part))
            ->implode('');
    }
}
