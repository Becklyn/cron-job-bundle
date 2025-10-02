<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Command;

use Becklyn\CronJobBundle\Console\BufferedConsoleOutput;
use Becklyn\CronJobBundle\Console\BufferedSymfonyStyle;
use Becklyn\CronJobBundle\Cron\CronJobInterface;
use Becklyn\CronJobBundle\Cron\CronJobRegistry;
use Becklyn\CronJobBundle\Data\WrappedJob;
use Becklyn\CronJobBundle\Entity\CronJobRun;
use Becklyn\CronJobBundle\Model\CronModel;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use function Sentry\captureException;
use Sentry\EventHint;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

#[AsCommand(name: "cron:run")]
class RunCommand extends Command
{
    private const RUN_SINGLE_JOB = "job";
    private const FORCED_RUN = "forced-run";
    private const DEFAULT_ANSWER = "Execute all Jobs";

    private readonly LockInterface $lock;
    private readonly string $maintenancePath;
    private readonly EntityManager $entityManager;

    public function __construct (
        private readonly CronJobRegistry $registry,
        private readonly CronModel $logModel,
        private readonly LoggerInterface $logger,
        LockFactory $lockFactory,
        ManagerRegistry $doctrine,
        string $projectDir,
        private readonly ?Profiler $profiler = null
    )
    {
        parent::__construct();

        /** @var EntityManager $entityManager */
        $entityManager = $doctrine->getManager();
        $this->lock = $lockFactory->createLock("cron-run", 600);
        $this->maintenancePath = \rtrim($projectDir, "/") . "/MAINTENANCE";
        $this->entityManager = $entityManager;

        $this->addOption(
            self::RUN_SINGLE_JOB,
            "s",
            InputOption::VALUE_REQUIRED,
            "Option that defines which single CronJob should be executed."
        );
        $this->addOption(
            self::FORCED_RUN,
            "f",
            InputOption::VALUE_NONE,
            "Option that forces one or multiple CronJobs to be executed."
        );
    }


    /**
     * @inheritDoc
     */
    protected function execute (InputInterface $input, OutputInterface $output) : int
    {
        $isInteractive = $input->isInteractive();
        $allCronJobs = $this->registry->getAllJobs();
        $cronJobsToExecute = [];

        $optionRunSingleJob = $input->getOption(self::RUN_SINGLE_JOB);
        $optionForcedRun = $input->getOption(self::FORCED_RUN);

        $bufferedOutput = BufferedConsoleOutput::createFromOutput($output);
        $io = new BufferedSymfonyStyle($input, $bufferedOutput);

        \ini_set("memory_limit", "-1");
        \set_time_limit(0);

        if (null !== $this->profiler)
        {
            $this->profiler->disable();
        }

        $io->title("Cron Jobs");

        if (\is_file($this->maintenancePath))
        {
            $io->warning("Can't run in MAINTENANCE mode.");

            return 3;
        }

        if (!$this->lock->acquire())
        {
            $io->warning("A previous cron command is still running.");

            return self::INVALID;
        }

        $commandStatus = self::FAILURE;

        if ($isInteractive && $optionForcedRun && null === $optionRunSingleJob)
        {
            $cronJobChoiceList = $this->buildCronJobChoiceList($allCronJobs);

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper("question");

            $question = (new ChoiceQuestion(
                "<fg=bright-magenta>Select the jobs you wan't to run. (Values must be comma seperated)</> \n",
                \array_keys($cronJobChoiceList),
                0
            ))
                ->setMultiselect(true)
                ->setErrorMessage("Invalid Cron Job '%s' selected. Please select a valid Cron Job to run.");

            $choices = $helper->ask($input, $output, $question);

            foreach ($choices as $choice)
            {
                if (self::DEFAULT_ANSWER === $choice)
                {
                    $cronJobsToExecute = $allCronJobs;

                    // We can break here since we're executing all cron jobs anyway.
                    break;
                }

                $selectedJob = $cronJobChoiceList[$choice] ?? null;

                if (null === $selectedJob)
                {
                    $io->writeln("<fg=red>Job key not found.</>");

                    return self::FAILURE;
                }

                $cronJobsToExecute[] = $selectedJob;
            }
        }
        elseif (null !== $optionRunSingleJob)
        {
            $selectedJob = $this->registry->getJobByKey($optionRunSingleJob);

            if (null === $selectedJob)
            {
                $io->writeln("<fg=red>Job key not found.</>");

                return self::FAILURE;
            }

            $cronJobsToExecute = [$selectedJob];
        }
        else
        {
            $cronJobsToExecute = $allCronJobs;
        }

        foreach ($cronJobsToExecute as $job)
        {
            $commandStatus &= $this->executeJobAndGetStatus($job, $io, $bufferedOutput, $optionForcedRun);
        }

        $this->lock->release();

        return $commandStatus;
    }


    /**
     * @param CronJobInterface[] $availableCronJobs
     *
     * @return array<string, ?CronJobInterface>
     */
    private function buildCronJobChoiceList (array $availableCronJobs) : array
    {
        $cronJobChoices = [
            self::DEFAULT_ANSWER => null,
        ];

        foreach ($availableCronJobs as $job)
        {
            $choice = $job->getName() . " -> " . $job::class;

            $cronJobChoices[$choice] = $job;
        }

        return $cronJobChoices;
    }


    public function executeJobAndGetStatus (
        CronJobInterface $job,
        BufferedSymfonyStyle $io,
        BufferedConsoleOutput $bufferedOutput,
        bool $optionForcedRun
    ) : int
    {
        $io->section('Job: ' . $job->getName());
        $now = new \DateTimeImmutable();
        $commandStatus = self::SUCCESS;

        try
        {
            $wrappedJob = new WrappedJob($job, $now);
        }
        catch (\InvalidArgumentException $invalidArgumentException)
        {
            $this->logger->error("Invalid cron tab given: {message}", [
                "message" => $invalidArgumentException->getMessage(),
                "exception" => $invalidArgumentException,
            ]);

            // Write error message
            $io->writeln("<fg=red>Command failed.</>");

            return self::FAILURE;
        }

        if (!$optionForcedRun && !$this->logModel->isDue($wrappedJob))
        {
            $io->writeln("<fg=yellow>Not due</>");

            return self::FAILURE;
        }

        try
        {
            $bufferedOutput->clearBufferedOutput();
            $status = $job->execute($io);
            $this->logModel->logRun($wrappedJob, $status);
            $this->logModel->flush();

            if (!$status->isSucceeded())
            {
                $commandStatus = self::FAILURE;
            }

            $io->writeln(
                $status->isSucceeded()
                    ? "<fg=green>Command succeeded.</>"
                    : "<fg=red>Command failed.</>"
            );

            return $commandStatus;
        }
        catch (\Exception $exception)
        {
            //region Report error to Sentry
            $eventHint = EventHint::fromArray([
                "exception" => $exception,
                "extra" => [
                    "cronJobInterface" => $job::class,
                    "cronJobName" => $job->getName(),
                    "cronJobForcedRun" => $optionForcedRun,
                ],
            ]);
            captureException($exception, $eventHint);
            //endregion

            $entityManager = $this->entityManager;

            // Try to recover a faulty Doctrine connection so we can persist our CronJobRun to prevent immediate re-runs.
            if (!$entityManager->isOpen())
            {
                try
                {
                    $entityManager = EntityManager::create(
                        $entityManager->getConnection(),
                        $entityManager->getConfiguration()
                    );
                }
                catch (\Exception $exception)
                {
                    $io->writeln("");
                    $io->writeln("");
                    $io->writeln(\sprintf(
                        "Could not recover re-opening previously closed EntityManager connection. Encountered the following exception: '%s'.",
                        $exception->getMessage()
                    ));
                }
            }

            $run = new CronJobRun(
                $wrappedJob->getKey(),
                false,
                $io->getBuffer(),
                $wrappedJob->getSupposedLastRun()
            );

            $entityManager->persist($run);
            $entityManager->flush();

            $this->logger->error("Running the cron job failed with an exception: {message}", [
                "message" => $exception->getMessage(),
                "exception" => $exception,
            ]);

            $io->writeln("<fg=red>Command failed.</>");

            return self::FAILURE;
        }
    }
}
