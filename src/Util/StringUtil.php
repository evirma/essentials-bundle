<?php /** @noinspection SpellCheckingInspection */

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Util;

use JetBrains\PhpStorm\Pure;

final class StringUtil
{
    final private function __construct()
    {}

    public static function lcfirst(?string $str, string $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }
        return mb_strtolower(mb_substr($str, 0, 1, $encoding)).mb_substr($str, 1, null, $encoding);
    }

    public static function lcfirstSmart(?string $str, string $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }

        $secondChar = mb_substr($str, 1, 1, 'UTF-8');
        if ($secondChar && ctype_upper($secondChar)) {
            return StringUtil::lcfirst($str, $encoding);
        }

        return $str;
    }

    public static function isRussian(?string $word): bool
    {
        if (!$word) {
            return false;
        }

        $upper = '#[ЙЦУКЕНГШЩЗХЪЁЭЖДЛОРПАВЫФЯЧСМИТЬБЮ]#usi';

        return (bool)preg_match($upper, $word);
    }

    #[Pure] public static function isLowerCase(?string $str): bool
    {
        return ($str === self::lower($str));
    }

    #[Pure] public static function isUpperCase(?string $str): bool
    {
        return ($str === self::upper($str));
    }

    public static function lower(?string $str, $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }

        return mb_strtolower($str, $encoding);
    }

    public static function upper(?string $str, string $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }

        return mb_strtoupper($str, $encoding);
    }

    public static function ucfirst(?string $str, string $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }

        return mb_strtoupper(mb_substr($str, 0, 1, $encoding)).mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
    }

    public static function ucwords(?string $str, string $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }

        return (string)mb_convert_case($str, MB_CASE_TITLE, $encoding);
    }

    public static function ucwordsSoft(?string $str, string $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }

        $str = trim(preg_replace('#\s+#usi', ' ', $str));
        $parts = explode(' ', $str);
        foreach ($parts as &$part) {
            $part = self::ucfirst($part, $encoding);
        }
        return implode(' ', $parts);
    }

    public static function strlen(?string $str, string $encoding = 'UTF-8'): int
    {
        if (!$str) {
            return 0;
        }

        return (int)mb_strlen($str, $encoding);
    }

    public static function substr(?string $str, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        if (!$str) {
            return '';
        }

        return mb_substr($str, $start, $length, $encoding);
    }

    public static function getDomain(?string $url, bool $withWww = true): string
    {
        if (!$url) {
            return '';
        }

        $result = parse_url($url, PHP_URL_HOST);
        if (!$withWww) {
            $result = preg_replace('#^www\.#i', '', $result);
        }

        return (string)$result;
    }

    #[Pure] public static function safeTruncate(?string $str, int $limit = 150): string
    {
        if (!$str) {
            return '';
        }

        return StringUtil::truncate($str, $limit, true);
    }

    public static function truncate(?string $str, int $length = 30, $preserve = false, string $separator = '...'): string
    {
        if (!$str) {
            return '';
        }

        if (mb_strlen($str, 'UTF-8') <= $length) {
            return $str;
        }

        if ($preserve) {
            // If breakpoint is on the last word, return the value without separator.
            if (false === ($breakpoint = mb_strpos($str, ' ', $length, 'UTF-8'))) {
                return $str;
            }
            $length = $breakpoint;
        }

        return rtrim(mb_substr($str, 0, $length, 'UTF-8')).$separator;
    }

    public static function getPathSect(?string $path, int $sect): string|null
    {
        if (!$path) {
            return null;
        }

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

    public static function removePathSect(?string $path, int $sect): string
    {
        if (!$path) {
            return '';
        }

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

    public static function humanSize(mixed $value, int $decimals = 1): string
    {
        if (null === $value || '' == (string)$value) {
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
