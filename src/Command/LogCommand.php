<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Command;

use Becklyn\CronJobBundle\Cron\CronJobInterface;
use Becklyn\CronJobBundle\Cron\CronJobRegistry;
use Becklyn\CronJobBundle\Data\WrappedJob;
use Becklyn\CronJobBundle\Model\CronModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogCommand extends Command
{
    public static $defaultName = "cron:log";


    /**
     * @var CronJobRegistry
     */
    private $registry;


    /**
     * @var CronModel
     */
    private $model;


    /**
     * @param CronJobRegistry $registry
     */
    public function __construct (CronJobRegistry $registry, CronModel $model)
    {
        parent::__construct();
        $this->registry = $registry;
        $this->model = $model;
    }


    /**
     * @inheritDoc
     */
    protected function configure ()
    {
        $this
            ->addOption("single", null, InputOption::VALUE_NONE, "Whether the log for a single job should be shown.");
    }


    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute (InputInterface $input, OutputInterface $output) : ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Cron Job Log");

        if ($input->getOption("single"))
        {
            return $this->logSingleJob($io);
        }

        return $this->logAllJobs($io);
    }


    private function logSingleJob (SymfonyStyle $io) : int
    {
        $jobs = $this->registry->getAllJobs();

        if (empty($jobs))
        {
            $io->error("No cron jobs registered.");
            return 1;
        }

        if (\count($jobs) > 1)
        {
            $selectedIndex = $io->choice(
                "Please choose job to inspect",
                \array_keys($jobs)
            );

            $job = $jobs[$selectedIndex];
        }
        else
        {
            $job = \array_values($jobs)[0];
        }

        $io->section("Job: {$job->getName()}");

        try
        {
            $wrappedJob = new WrappedJob($job, new \DateTimeImmutable());
            $runs = $this->model->findMostRecentRuns($wrappedJob, 10);

            if (empty($runs))
            {
                $io->comment("No runs recorded for this job.");
                return 0;
            }

            $rows = [];

            foreach ($runs as $run)
            {
                $rows[] = [
                    $run->getTimeRun()->format("d.m.Y H:i"),
                    $run->isSuccessful() ? "<fg=green>yes</>" : "<fg=red>no</>",
                    $run->getLog()
                ];
            }

            $io->table(["Date", "Successful?", "Log"], $rows);
            return 0;
        }
        catch (\InvalidArgumentException $exception)
        {
            $io->error("Invalid cron tab definition: {$exception->getMessage()}");
            return 1;
        }
    }


    /**
     * Logs an overview of all jobs
     *
     * @param SymfonyStyle $io
     *
     * @return int
     */
    private function logAllJobs (SymfonyStyle $io) : int
    {
        $invalidRows = [];
        /** @var WrappedJob[] $valid */
        $valid = [];
        $now = new \DateTimeImmutable();

        foreach ($this->registry->getAllJobs() as $job)
        {
            try
            {
                $valid[] = new WrappedJob($job, $now);
            }
            catch (\InvalidArgumentException $exception)
            {
                $invalidRows[] = [
                    "<fg=yellow>{$job->getName()}</>",
                    $exception->getMessage(),
                ];
            }
        }

        if (!empty($valid))
        {
            $io->section("Jobs Overview");
            $rows = [];

            foreach ($valid as $validJob)
            {
                $lastRun = $this->model->findLastRun($validJob);

                $rows[] = [
                    "<fg=yellow>{$validJob->getJob()->getName()}</>",
                    $validJob->getJob()->getCronTab(),
                    $lastRun
                        ? $lastRun->getTimeRun()->format("d.m.Y H:i")
                        : "—",
                    $lastRun->getLog(),
                    $validJob->isDue($lastRun) ? "<fg=red>yes</>" : "<fg=green>no</>",
                ];
            }

            $io->table(["Job", "Cron Tab", "Last Run", "Log", "Is due?"], $rows);
        }

        if (!empty($invalidRows))
        {
            $io->section("Invalid Job Definitions");
            $io->warning("These job definitions are skipped, as their cron tab is invalid.");
            $io->table(["Job", "Error"], $invalidRows);

            return 1;
        }

        return 0;
    }
}