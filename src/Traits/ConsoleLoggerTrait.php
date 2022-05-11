<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @deprecated
 */
trait ConsoleLoggerTrait
{
    protected LoggerInterface $logger;

    #[Required]
    public function setLogger(LoggerInterface $consoleLogger): void
    {
        $this->logger = $consoleLogger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
