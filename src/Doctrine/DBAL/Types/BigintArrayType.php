<?php
declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Doctrine\DBAL\Types;

use InvalidArgumentException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class BigintArrayType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'bigint[]';
    }

    /**
     * @return array
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return [];
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;
        return explode('","', trim($value, '{""}') );
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        settype($value, 'array'); // can be called with a scalar or array
        foreach ($value as $t) {
            if (!is_numeric($t)) {
                throw new InvalidArgumentException(sprintf('%s is not a properly numeric.', $t));
            }
        }

        return '{' . implode(",", $value) . '}';
    }

    public function getName(): string
    {
        return 'bigint[]';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}