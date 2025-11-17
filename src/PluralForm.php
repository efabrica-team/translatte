<?php

declare(strict_types=1);

namespace Efabrica\Translatte;

final class PluralForm
{
    public static function get(int $number, string $lang): int
    {
        if ($lang === 'pt_BR') {
            // temporary set a locale for brazilian
            $lang = 'xbr';
        }

        if (strlen($lang) > 3) {
            $lang = substr($lang, 0, -strlen((string) strrchr($lang, '_')));
        }

        if (in_array($lang, ['az', 'bo', 'dz', 'id', 'ja', 'jv', 'ka', 'km', 'kn', 'ko', 'ms', 'th', 'tr', 'vi', 'zh'], true)) {
            return 0;
        }

        if (in_array($lang, ['af', 'bn', 'bg', 'ca', 'da', 'de', 'el', 'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fo', 'fur', 'fy', 'gl', 'gu', 'ha', 'he', 'hu', 'is', 'it', 'ku', 'lb', 'ml', 'mn', 'mr', 'nah', 'nb', 'ne', 'nl', 'nn', 'no', 'oc', 'om', 'or', 'pa', 'pap', 'ps', 'pt', 'so', 'sq', 'sv', 'sw', 'ta', 'te', 'tk', 'ur', 'zu'], true)) {
            return ($number === 1) ? 0 : 1;
        }

        if (in_array($lang, ['am', 'bh', 'fil', 'fr', 'gun', 'hi', 'hy', 'ln', 'mg', 'nso', 'xbr', 'ti', 'wa'], true)) {
            return (($number === 0) || ($number === 1)) ? 0 : 1;
        }

        if (in_array($lang, ['be', 'bs', 'hr', 'ru', 'sh', 'sr', 'uk'], true)) {
            return ((1 == $number % 10) && (11 != $number % 100)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
        }

        if (in_array($lang, ['cs', 'sk'], true)) {
            return (1 == $number) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);
        }

        if (in_array($lang, ['ga'], true)) {
            return (1 == $number) ? 0 : ((2 == $number) ? 1 : 2);
        }

        if (in_array($lang, ['lt'], true)) {
            return ((1 == $number % 10) && (11 != $number % 100)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
        }

        if (in_array($lang, ['sl'], true)) {
            return (1 == $number % 100) ? 0 : ((2 == $number % 100) ? 1 : (((3 == $number % 100) || (4 == $number % 100)) ? 2 : 3));
        }

        if (in_array($lang, ['mk'], true)) {
            return (1 == $number % 10) ? 0 : 1;
        }

        if (in_array($lang, ['mt'], true)) {
            return (1 == $number) ? 0 : (((0 == $number) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3));
        }

        if (in_array($lang, ['lv'], true)) {
            return (0 == $number) ? 0 : (((1 == $number % 10) && (11 != $number % 100)) ? 1 : 2);
        }

        if (in_array($lang, ['pl'], true)) {
            return (1 == $number) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);
        }

        if (in_array($lang, ['cy'], true)) {
            return (1 == $number) ? 0 : ((2 == $number) ? 1 : (((8 == $number) || (11 == $number)) ? 2 : 3));
        }

        if (in_array($lang, ['ro'], true)) {
            return (1 == $number) ? 0 : (((0 == $number) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);
        }

        if (in_array($lang, ['ar'], true)) {
            return (0 == $number) ? 0 : ((1 == $number) ? 1 : ((2 == $number) ? 2 : ((($number % 100 >= 3) && ($number % 100 <= 10)) ? 3 : ((($number % 100 >= 11)) ? 4 : 5))));
        }

        return 0;
    }
}
