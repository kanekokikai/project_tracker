<?php

namespace App\Support;

class MessageText
{
    private const TRAILING = ".,!?;:'、。…";

    public static function html(?string $text): string
    {
        $text = (string) $text;
        $parts = preg_split('/(https?:\/\/[A-Za-z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]+)/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return nl2br(e($text), false);
        }

        $html = '';

        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }

            if ($index % 2 === 1) {
                [$url, $trailing] = self::splitTrailing($part);

                if (preg_match('/\Ahttps?:\/\/[A-Za-z0-9]/i', $url) === 1) {
                    $html .= '<a href="'.e($url).'" class="message-link" target="_blank" rel="noopener noreferrer">'.e($url).'</a>'.e($trailing);
                    continue;
                }
            }

            $html .= e($part);
        }

        return nl2br($html, false);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitTrailing(string $url): array
    {
        $trailing = '';

        while ($url !== '') {
            $last = mb_substr($url, -1);

            if (! str_contains(self::TRAILING, $last)) {
                break;
            }

            $trailing = $last.$trailing;
            $url = mb_substr($url, 0, -1);
        }

        $open = substr_count($url, '(');
        $close = substr_count($url, ')');

        while ($url !== '' && $close > $open && str_ends_with($url, ')')) {
            $trailing = ')'.$trailing;
            $url = substr($url, 0, -1);
            $close--;
        }

        return [$url, $trailing];
    }
}
