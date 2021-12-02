<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Util;

use ArrayObject;

class ArrayUtil
{
    public static function reindexArray(array $array, string $keyToIndex = 'id'): array
    {
        $result = [];

        foreach ($array as $item) {
            if (is_scalar($item)) {
                $result[(int)$item] = (int)$item;
            } elseif (isset($item[$keyToIndex])) {
                $result[$item[$keyToIndex]] = $item;
            }
        }

        return $result;
    }

    public static function stringify(array $array): array
    {
        foreach ($array as &$item) {
            if (is_numeric($item)) {
                $item = (string)$item;
                continue;
            }

            if (is_object($item) || is_array($item)) {
                $item = ArrayUtil::stringify($item);
            }
        } // recurse!

        return $array;
    }

    public static function arrayMergeRecursiveDistinct(array $array1, array $array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset ($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = ArrayUtil::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function hash(array $array): string
    {
        array_multisort($array);
        return md5(serialize($array));
    }

    public static function hasKeys($keys, array|ArrayObject $array): bool
    {
        if (empty($array)) {
            return false;
        }

        if (!is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            if (isset($array[$key]) || array_key_exists($key, $array)) {
                continue;
            } else {
                return false;
            }
        }

        return true;
    }

    public static function getColumn(array $array, string|int $columnName, string $cast = null): array
    {
        $result = [];
        foreach ($array as $item) {
            if (isset($item[$columnName]) || array_key_exists($columnName, $item)) {
                $result[] = match ($cast) {
                    'int' => (int)$item[$columnName],
                    'string' => (string)$item[$columnName],
                    'bool' => (bool)$item[$columnName],
                    default => $item[$columnName],
                };
            }
        }

        return $result;
    }
}
