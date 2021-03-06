<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Doctrine\DBAL\Types;

use InvalidArgumentException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class InetType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'inet';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }
        if (preg_match('/^((25[0-5]|2[0-4]\d|[01]?\d\d?).(25[0-5]|2[0-4]\d|[01]?\d\d?).(25[0-5]|2[0-4]\d|[01]?\d\d?).(25[0-5]|2[0-4]\d|[01]?\d\d?))|((([\dA-Fa-f]{1,4}:){7}[\dA-Fa-f]{1,4})|(([\dA-Fa-f]{1,4}:){6}:[\dA-Fa-f]{1,4})|(([\dA-Fa-f]{1,4}:){5}:([\dA-Fa-f]{1,4}:)?[\dA-Fa-f]{1,4})|(([\dA-Fa-f]{1,4}:){4}:([\dA-Fa-f]{1,4}:){0,2}[\dA-Fa-f]{1,4})|(([\dA-Fa-f]{1,4}:){3}:([\dA-Fa-f]{1,4}:){0,3}[\dA-Fa-f]{1,4})|(([\dA-Fa-f]{1,4}:){2}:([\dA-Fa-f]{1,4}:){0,4}[\dA-Fa-f]{1,4})|(([\dA-Fa-f]{1,4}:){6}((b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b).){3}(b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b))|(([\dA-Fa-f]{1,4}:){0,5}:((b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b).){3}(b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b))|(::([\dA-Fa-f]{1,4}:){0,5}((b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b).){3}(b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b))|([\dA-Fa-f]{1,4}::([\dA-Fa-f]{1,4}:){0,5}[\dA-Fa-f]{1,4})|(::([\dA-Fa-f]{1,4}:){0,6}[\dA-Fa-f]{1,4})|(([\dA-Fa-f]{1,4}:){1,7}:))$/', $value)) {
            return $value;
        }
        throw new InvalidArgumentException(sprintf('%s is not a properly formatted INET type.', $value));
    }

    public function getName(): string
    {
        return 'inet';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
