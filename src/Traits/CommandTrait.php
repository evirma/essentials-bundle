<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method LoggerInterface getLogger()
 */
trait CommandTrait
{
    protected function outputCommandHelp(Command $command, InputInterface $input, OutputInterface $output): void
    {
        $help = new HelpCommand();
        $help->setCommand($command);

        try {
            $help->run($input, $output);
        } catch (\Exception|ExceptionInterface $e) {
            if (method_exists($this, 'getLogger')) {
                $this->getLogger()->error("Help command failed: ".$e->getMessage());
            }
        }
    }

    protected function logMemoryUsage(): void
    {
        if (method_exists($this, 'getLogger')) {
            $this->getLogger()->info(/** @lang text */ ' <info>MEMORY USAGE:</info> <fg=white;options=bold>'.$this->getMemoryUsage().'</>');
        }
    }

    protected function getMemoryUsage(): string
    {
        return round((memory_get_usage() / 1024 / 1024), 2).'Mb';
    }
}
