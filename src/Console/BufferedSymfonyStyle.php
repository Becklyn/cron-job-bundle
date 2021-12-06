<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class BufferedSymfonyStyle extends SymfonyStyle
{
    private OutputInterface $output;


    /**
     * @inheritDoc
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);
        $this->output = $output;
    }


    public function getBuffer () : string
    {
        return ($this->output instanceof BufferedConsoleOutput)
            ? $this->output->getBufferedOutput()
            : "";
    }
}
