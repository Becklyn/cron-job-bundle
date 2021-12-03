<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Command;

use Becklyn\CronJobBundle\Console\BufferedConsoleOutput;
use Becklyn\CronJobBundle\Console\BufferedSymfonyStyle;
use Becklyn\CronJobBundle\Cron\CronJobInterface;
use Becklyn\CronJobBundle\Cron\CronJobRegistry;
use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Data\WrappedJob;
use Becklyn\CronJobBundle\Model\CronModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 *
 */
class RunCommand extends Command
{
    public static $defaultName = "cron:run";

    private const RUN_SINGLE_JOB = "job";
    private const FORCED_RUN = "forced-run";
    private const DEFAULT_ANSWER = "Execute all Jobs";

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

    /** @var Profiler|null */
    private $profiler;


    /**
     */
    public function __construct (
        CronJobRegistry $registry,
        CronModel $model,
        LoggerInterface $logger,
        LockFactory $lockFactory,
        string $projectDir,
        ?Profiler $profiler = null
    )
    {
        parent::__construct();
        $this->registry = $registry;
        $this->logModel = $model;
        $this->logger = $logger;
        $this->lock = $lockFactory->createLock("cron-run", 600);
        $this->maintenancePath = \rtrim($projectDir, "/") . "/MAINTENANCE";
        $this->profiler = $profiler;
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
    protected function execute (InputInterface $input, OutputInterface $output) : ?int
    {
        $isInteractive = $input->isInteractive();
        $jobs = $this->registry->getAllJobs();
        $allowedAnswers = [self::DEFAULT_ANSWER];

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
            foreach ($jobs as $job)
            {
                $allowedAnswers[] = $job->getName() . " -> " . \get_class($job);
            }

            $helper = $this->getHelper("question");
            $question = (new ChoiceQuestion(
                "<fg=bright-magenta>Select the jobs you wan't to run. (Values must be comma seperated)</> \n",
                $allowedAnswers,
                0
            ))
                ->setMultiselect(true)
                ->setErrorMessage("invalid choice");

            $choices = $helper->ask($input, $output, $question);

            foreach ($choices as $key => $choice)
            {
                if (self::DEFAULT_ANSWER === $choice)
                {
                    foreach ($jobs as $job)
                    {
                        $commandStatus = $this->executeJobAndGetStatus($job, $io, $bufferedOutput, $optionForcedRun);
                    }

                    $this->lock->release();
                    return $commandStatus;
                }

                $selectedJob = $this->registry->getJobByChoice($choice);

                if (null === $selectedJob)
                {
                    $io->writeln("<fg=red>Job key not found.</>");
                    return self::FAILURE;
                }

                $commandStatus = $this->executeJobAndGetStatus($selectedJob, $io, $bufferedOutput, $optionForcedRun);
            }
        }

        if (null !== $optionRunSingleJob)
        {
            $job = $this->registry->getJobByKey($optionRunSingleJob);

            if (null === $job)
            {
                $io->writeln("<fg=red>Job key not found.</>");
                return self::FAILURE;
            }

            return $this->executeJobAndGetStatus($job, $io, $bufferedOutput, $optionForcedRun);
        }

        if ((!$isInteractive && $optionForcedRun) || ($isInteractive && !$optionForcedRun))
        {
            foreach ($jobs as $job)
            {
                $commandStatus = $this->executeJobAndGetStatus($job, $io, $bufferedOutput, $optionForcedRun);
            }
        }

        $this->lock->release();
        return $commandStatus;
    }


    public function executeJobAndGetStatus (
        CronJobInterface $job,
        BufferedSymfonyStyle $io,
        BufferedConsoleOutput $bufferedOutput,
        bool $optionForcedRun
    ) : int
    {
        $io->section("Job: {$job->getName()}");
        $now = new \DateTimeImmutable();
        $commandStatus = self::SUCCESS;

        try
        {
            $wrappedJob = new WrappedJob($job, $now);
        }
        catch (\InvalidArgumentException $exception)
        {
            $this->logger->error("Invalid cron tab given: {message}", [
                "message" => $exception->getMessage(),
                "exception" => $exception,
            ]);

            // Write error message
            $io->writeln("<fg=red>Command failed.</>");
            return self::FAILURE;
        }

        if (!$this->logModel->isDue($wrappedJob) && !$optionForcedRun)
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

        return $commandStatus;
    }
}
