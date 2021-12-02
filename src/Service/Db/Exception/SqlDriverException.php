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
     * Returns the driver specific error code if given.
     * Returns null if no error code was given by the driver.
     *
     * @return int
     */
    public function getErrorCode(): int
    {
        if ($this->driverException instanceof DBALDriverException) {
            return $this->driverException->getCode();
        }

        return $this->getCode();
    }

    /**
     * Returns the SQLSTATE the driver was in at the time the error occurred, if given.
     *
     * Returns null if no SQLSTATE was given by the driver.
     *
     * @return string|null
     */
    public function getSQLState(): ?string
    {
        if ($this->driverException instanceof DBALDriverException) {
            return $this->driverException->getSQLState();
        }

        return null;
    }

}