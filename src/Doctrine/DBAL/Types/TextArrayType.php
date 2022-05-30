<?php
declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TextArrayType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (empty($value)) {
            return '{}';
        }

        $result = '';
        foreach ($value as $part) {
            if (null === $part) {
                $result .= 'NULL,';
                continue;
            }
            if ('' === $part) {
                $result .= '"",';
                continue;
            }

            $result .= '"'.addcslashes($part, '"').'",';
        }

        return '{'.substr($result, 0, -1).'}';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (empty($value) || '{}' === $value) {
            return array();
        }

        // @see http://stackoverflow.com/a/19082849/1160901
        preg_match_all('/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|\d+|x[\da-f]+))*)"\s*)(,|(?<!^\{)(?=}$))/i', $value, $matches, PREG_SET_ORDER);

        $array = array();
        foreach ($matches as $match) {
            if ('' !== $match[3]) {
                $array[] = stripcslashes($match[3]);
                continue;
            }

            $array[] = 'NULL' === $match[2] ? null : $match[2];
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'text_array';
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'text[]';
    }
}
