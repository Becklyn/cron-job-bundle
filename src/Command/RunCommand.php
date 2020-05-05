<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Command;

use Becklyn\CronJobBundle\Console\BufferedConsoleOutput;
use Becklyn\CronJobBundle\Console\BufferedSymfonyStyle;
use Becklyn\CronJobBundle\Cron\CronJobRegistry;
use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Data\WrappedJob;
use Becklyn\CronJobBundle\Model\CronModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 *
 */
class RunCommand extends Command
{
    public static $defaultName = "cron:run";

    /** @var CronJobRegistry */
    private $registry;

    /** @var CronModel */
    private $logModel;

    /** @var LoggerInterface */
    private $logger;

    /** * @var LockInterface */
    private $lock;

    /** @var string */
    private $maintenancePath;


    /**
     */
    public function __construct (
        CronJobRegistry $registry,
        CronModel $model,
        LoggerInterface $logger,
        LockFactory $lockFactory,
        string $projectDir
    )
    {
        parent::__construct();
        $this->registry = $registry;
        $this->logModel = $model;
        $this->logger = $logger;
        $this->lock = $lockFactory->createLock("cron-run");
        $this->maintenancePath = \rtrim($projectDir, "/") . "/MAINTENANCE";
    }


    /**
     * @inheritDoc
     */
    protected function execute (InputInterface $input, OutputInterface $output) : ?int
    {
        $bufferedOutput = BufferedConsoleOutput::createFromOutput($output);
        $io = new BufferedSymfonyStyle($input, $bufferedOutput);

        $io->title("Cron Jobs");

        if (\is_file($this->maintenancePath))
        {
            $io->warning("Can't run in MAINTENANCE mode.");
            return 3;
        }

        if (!$this->lock->acquire())
        {
            $io->warning("A previous cron command is still running.");
            return 2;
        }

        $now = new \DateTimeImmutable();
        $jobFailed = false;

        foreach ($this->registry->getAllJobs() as $job)
        {
            $io->section("Job: {$job->getName()}");

            try
            {
                $wrappedJob = new WrappedJob($job, $now);
            }
            catch (\InvalidArgumentException $exception)
            {
                $jobFailed = true;
                $this->logger->error("Invalid cron tab given: {message}", [
                    "message" => $exception->getMessage(),
                    "exception" => $exception,
                ]);

                // Write error message
                $io->writeln("<fg=red>Command failed.</>");
            }

            if (!$this->logModel->isDue($wrappedJob))
            {
                $io->writeln("<fg=yellow>Not due</>");
                continue;
            }

            try
            {
                $bufferedOutput->clearBufferedOutput();
                $status = $job->execute($io);
                $this->logModel->logRun($wrappedJob, $status);
                $this->logModel->flush();

                if (!$status->isSucceeded())
                {
                    $jobFailed = true;
                }

                $io->writeln(
                    $status->isSucceeded()
                        ? "<fg=green>Command succeeded.</>"
                        : "<fg=red>Command failed.</>"
                );
            }
            catch (\Exception $e)
            {
                $this->logModel->logRun($wrappedJob, new CronStatus(false));
                $this->logModel->flush();
                $this->logger->error("Running the cron job failed with an exception: {message}", [
                    "message" => $e->getMessage(),
                    "exception" => $e,
                ]);

                $io->writeln("<fg=red>Command failed.</>");
            }
        }

        $this->lock->release();
        return $jobFailed ? 1 : 0;
    }
}
