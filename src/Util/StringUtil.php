<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Util;

use JetBrains\PhpStorm\Pure;

final class StringUtil
{
    final private function __construct()
    {}

    public static function lcfirst(string $str, string $encoding = 'UTF-8'): string
    {
        return mb_strtolower(mb_substr($str, 0, 1, $encoding)).mb_substr($str, 1, null, $encoding);
    }

    public static function lcfirstSmart(string $str, string $encoding = 'UTF-8'): string
    {
        $secondChar = mb_substr($str, 1, 1, 'UTF-8');
        if ($secondChar && ctype_upper($secondChar)) {
            return StringUtil::lcfirst($str, $encoding);
        }

        return $str;
    }

    public static function isRussian($word): bool
    {
        $upper = '#[ЙЦУКЕНГШЩЗХЪЁЭЖДЛОРПАВЫФЯЧСМИТЬБЮ]#usi';

        return (bool)preg_match($upper, (string)$word);
    }

    #[Pure] public static function isLowerCase(string $str): bool
    {
        return ($str === self::lower($str));
    }

    #[Pure] public static function isUpperCase(string $str): bool
    {
        return ($str === self::upper($str));
    }

    public static function lower(string $str, $encoding = 'UTF-8'): string
    {
        return mb_strtolower($str, $encoding);
    }

    public static function upper(string $str, string $encoding = 'UTF-8'): string
    {
        return mb_strtoupper($str, $encoding);
    }

    public static function ucfirst(string $str, string $encoding = 'UTF-8'): string
    {
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding)).mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
    }

    public static function ucwords(string $str, string $encoding = 'UTF-8'): string
    {
        return (string)mb_convert_case($str, MB_CASE_TITLE, $encoding);
    }

    public static function ucwordsSoft(string $str, string $encoding = 'UTF-8'): string
    {
        $str = trim(preg_replace('#\s+#usi', ' ', $str));
        $parts = explode(' ', $str);
        foreach ($parts as &$part) {
            $part = self::ucfirst($part, $encoding);
        }
        return implode(' ', $parts);
    }

    public static function strlen(string $str, string $encoding = 'UTF-8'): int
    {
        return (int)mb_strlen($str, $encoding);
    }

    public static function substr(string $str, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        return mb_substr($str, $start, $length, $encoding);
    }

    public static function getDomain(string $url, bool $withWww = true): string
    {
        $result = parse_url($url, PHP_URL_HOST);
        if (!$withWww) {
            $result = preg_replace('#^www\.#i', '', $result);
        }

        return (string)$result;
    }

    #[Pure] public static function safeTruncate(string $string, int $limit = 150): string
    {
        return StringUtil::truncate($string, $limit, true);
    }

    public static function truncate(string $value, int $length = 30, $preserve = false, string $separator = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $length) {
            return $value;
        }

        if ($preserve) {
            // If breakpoint is on the last word, return the value without separator.
            if (false === ($breakpoint = mb_strpos($value, ' ', $length, 'UTF-8'))) {
                return $value;
            }
            $length = $breakpoint;
        }

        return rtrim(mb_substr($value, 0, $length, 'UTF-8')).$separator;
    }

    public static function getPathSect(string $path, int $sect): string|null
    {
        $trimmedPath = trim($path, '/');
        $pathArray = explode('/', $trimmedPath);

        if (!$cnt = count($pathArray)) {
            return null;
        }

        if ($sect < 0) {
            $cnt = $cnt + $sect;
        }

        return $pathArray[$cnt] ?? null;
    }

    public static function removePathSect(string $path, int $sect): string
    {
        $trimmedPath = trim($path, '/');
        $pathArray = explode('/', $trimmedPath);

        if (!$cnt = count($pathArray)) {
            return '';
        }

        if ($sect < 0) {
            $cnt = $cnt + $sect;
        }

        unset($pathArray[$cnt]);

        $result = '';
        foreach ($pathArray as $slug) {
            $result .= '/'.$slug;
        }

        return $result;
    }

    public static function humanSize($value, $decimals = 1): string
    {
        if (null === $value) {
            return '';
        }

        $sz = ['b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb'];
        $factor = intval(floor((strlen($value) - 1) / 3));

        $size = $value / pow(1024, $factor);
        if ($size == ceil($size)) {
            $decimals = 0;
        }

        return sprintf("%.{$decimals}f", $value / pow(1024, $factor)).$sz[$factor];
    }
}
