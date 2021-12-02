<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Util;

use Symfony\Component\Yaml\Yaml as YamlParser;

class YamlUtil
{
    /**
     * @see FileFormatterInterface::encode()
     */
    public static function encode(mixed $input, int $inline = 2, int $indent = 4): string
    {
        return YamlParser::dump(
            $input,
            $inline,
            $indent,
            YamlParser::DUMP_EXCEPTION_ON_INVALID_TYPE
        );
    }

    /**
     * @see FileFormatterInterface::decode()
     */
    public static function decode($data): array
    {
        return (array) YamlParser::parse($data);
    }
}
