<?php

declare(strict_types=1);

namespace Evirma\Bundle\CoreBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TsvectorType extends Type
{
    public function getName(): string
    {
        return 'tsvector';
    }

    public function canRequireSQLConversion(): bool
    {
        return true;
    }

    public function getSqlDeclaration(array $column, AbstractPlatform $platform): string
    {
        return "TSVECTOR";
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $value;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return sprintf('to_tsvector(%s)', $sqlExpr);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (is_array($value)) {
            $value = implode(" ", $value);
        }
        return $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}