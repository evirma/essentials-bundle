<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Util;

class JsonUtil
{
    public static function encode(mixed $data): string
    {
        return (string)json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function encodeStringify(mixed $data): string
    {
        return (string)json_encode(ArrayUtil::stringify($data), JSON_UNESCAPED_UNICODE);
    }

    public static function decode(string $data, int $depth = 512): array
    {
        return (array)json_decode($data, true, $depth);
    }
}
