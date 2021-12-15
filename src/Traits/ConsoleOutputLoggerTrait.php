<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @deprecated
 * @property ContainerInterface $container
 */
trait ConsoleOutputLoggerTrait
{
    protected LoggerInterface $logger;

    #[Required]
    public function setLogger(LoggerInterface $consoleLogger)
    {
        $this->logger = $consoleLogger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
