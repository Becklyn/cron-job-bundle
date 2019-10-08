<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Command;

use Becklyn\CronJobBundle\Cron\CronJobRegistry;
use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Data\WrappedJob;
use Becklyn\CronJobBundle\Model\CronModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCommand extends Command
{
    public static $defaultName = "cron:run";


    /**
     * @var CronJobRegistry
     */
    private $registry;


    /**
     * @var CronModel
     */
    private $logModel;


    /**
     * @var LoggerInterface
     */
    private $logger;


    public function __construct (CronJobRegistry $registry, CronModel $model, LoggerInterface $logger)
    {
        parent::__construct();
        $this->registry = $registry;
        $this->logModel = $model;
        $this->logger = $logger;
    }


    /**
     * @inheritDoc
     */
    protected function execute (InputInterface $input, OutputInterface $output) : ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Cron Jobs");

        $now = new \DateTimeImmutable();
        $jobFailed = false;

        foreach ($this->registry->getAllJobs() as $job)
        {
            $io->section("Job: {$job->getName()}");

            try
            {
                $wrappedJob = new WrappedJob($job, $now);

                if (!$this->logModel->isDue($wrappedJob))
                {
                    $io->writeln("<fg=yellow>Not due</>");
                    continue;
                }

                try
                {
                    $status = $job->execute($io);
                    $this->logModel->logRun($wrappedJob, $status);
                    $this->logModel->flush();

                    if (!$status->isSuccessful())
                    {
                        $jobFailed = true;
                    }

                    $io->writeln(
                        $status->isSuccessful()
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
        }

        return $jobFailed ? 0 : 1;
    }
}
