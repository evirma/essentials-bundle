<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Service\Db\Exception;

use Doctrine\DBAL\Driver\Exception as DBALDriverException;
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