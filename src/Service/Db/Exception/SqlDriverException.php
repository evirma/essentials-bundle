<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Service\Db\Exception;

use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RuntimeException;
use Throwable;

class SqlDriverException extends RuntimeException
{
    private Throwable $driverException;

    public function __construct(string $message, Throwable $exception)
    {
        $this->driverException = $exception;
        parent::__construct($message, $exception->getCode(), $exception);
    }

    public function isDedlock(): bool
    {
        return $this->driverException instanceof DeadlockException;
    }

    public function isConstraintViolation(): bool
    {
        return $this->driverException instanceof ConstraintViolationException;
    }

    public function isLockWaitTimeout(): bool
    {
        return $this->driverException instanceof LockWaitTimeoutException;
    }

    public function isUniqueConstraintViolation(): bool
    {
        return $this->driverException instanceof UniqueConstraintViolationException;
    }

    /**
     * @return int|string
     */
    public function getErrorCode(): int|string
    {
        if ($this->driverException instanceof DBALDriverException) {
            return $this->driverException->getCode();
        }

        return (int)$this->getCode();
    }

    public function getSQLState(): ?string
    {
        if ($this->driverException instanceof DBALDriverException) {
            return $this->driverException->getSQLState();
        }

        return null;
    }

}
