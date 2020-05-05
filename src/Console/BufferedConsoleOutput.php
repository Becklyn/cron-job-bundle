<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Console;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class BufferedConsoleOutput extends ConsoleOutput
{
    private $buffer = "";

    /**
     * @inheritDoc
     */
    public function __construct (int $verbosity = self::VERBOSITY_NORMAL, ?bool $decorated = null, ?OutputFormatterInterface $formatter = null)
    {
        parent::__construct($verbosity, $decorated, $formatter);
    }


    protected function doWrite (string $message, bool $newline) : void
    {
        parent::doWrite($message, $newline);

        $this->buffer .= $message;

        if ($newline)
        {
            $this->buffer .= \PHP_EOL;
        }
    }


    /**
     */
    public function getBufferedOutput () : string
    {
        return $this->buffer;
    }


    /**
     *
     */
    public function clearBufferedOutput () : void
    {
        $this->buffer = "";
    }


    /**
     * Generates a new output from the given output
     */
    public static function createFromOutput (OutputInterface $output) : self
    {
        return new self(
            $output->getVerbosity(),
            $output->isDecorated(),
            $output->getFormatter()
        );
    }
}
