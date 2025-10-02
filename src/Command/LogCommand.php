<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Command;

use Becklyn\CronJobBundle\Cron\CronJobRegistry;
use Becklyn\CronJobBundle\Data\WrappedJob;
use Becklyn\CronJobBundle\Entity\CronJobRun;
use Becklyn\CronJobBundle\Model\CronModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: "cron:log")]
class LogCommand extends Command
{
    public function __construct (private readonly CronJobRegistry $registry, private readonly CronModel $model)
    {
        parent::__construct();
    }


    /**
     * @inheritDoc
     */
    protected function configure () : void
    {
        $this
            ->addOption("single", null, InputOption::VALUE_OPTIONAL, "Whether the log for a single job should be shown. You can optionally provide a FQCN to a cron task to only show its logs.", false);
    }


    protected function execute (InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Cron Job Log");
        $single = $input->getOption("single");

        if (!$this->registry->hasJobs())
        {
            $io->comment("No jobs registered");
            return 0;
        }

        if (false !== $single)
        {
            $selectedTask = null !== $single
                ? (string) $single
                : null;

            return $this->logSingleJob($io, $selectedTask);
        }

        return $this->logAllJobs($io);
    }


    /**
     * Logs the details.
     */
    private function logSingleJob (SymfonyStyle $io, ?string $preselected) : int
    {
        $jobs = $this->registry->getAllJobs();

        if (null !== $preselected)
        {
            $job = $jobs[$preselected] ?? null;

            if (null === $job)
            {
                $io->error(\sprintf("No cron job with key '%s' registered.", $preselected));
                return 1;
            }
        }
        elseif (\count($jobs) > 1)
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

        $io->section('Job: ' . $job->getName());

        try
        {
            $wrappedJob = new WrappedJob($job, new \DateTimeImmutable());
            $runs = $this->model->findMostRecentRuns($wrappedJob, 10);
            $io->comment(\sprintf('Key: <fg=yellow>%s</>', $wrappedJob->getKey()));
            $io->comment('Next run: ' . $this->formatNextRun($wrappedJob, $runs[0] ?? null));

            if ([] === $runs)
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
                    $run->getLog(),
                ];
            }

            $io->table(["Date", "Succeeded?", "Log"], $rows);
            return 0;
        }
        catch (\InvalidArgumentException $invalidArgumentException)
        {
            $io->error('Invalid cron tab definition: ' . $invalidArgumentException->getMessage());
            return 1;
        }
    }


    /**
     * Logs an overview of all jobs.
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
                    \sprintf('<fg=yellow>%s</>', $job->getName()),
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
                    \sprintf('<fg=yellow>%s</>', $validJob->getJob()->getName()),
                    $validJob->getJob()->getCronTab(),
                    $this->formatLastRun($lastRun),
                    $lastRun ? $lastRun->getLog() : "",
                    $this->formatNextRun($validJob, $lastRun),
                ];
            }

            $io->table(["Job", "Cron Tab", "Last Run", "Log", "Next Run"], $rows);
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


    private function formatLastRun (?CronJobRun $lastRun) : string
    {
        if (null === $lastRun)
        {
            return "—";
        }

        $color = "green";
        $suffix = " (ok)";

        if (!$lastRun->isSuccessful())
        {
            $color = "red";
            $suffix = " (failed)";
        }

        return \sprintf('<fg=%s>%s%s</>', $color, $lastRun->getTimeRun()->format("d.m.Y H:i"), $suffix);
    }


    private function formatNextRun (WrappedJob $job, ?CronJobRun $lastRun) : string
    {
        $isDue = $job->isDue($lastRun);
        $nextRunColor = "green";
        $nextRunSuffix = "";

        if ($isDue)
        {
            $nextRunColor = "red";
            $nextRunSuffix = " (due)";
        }

        return \sprintf('<fg=%s>%s%s</>', $nextRunColor, $job->getNextRun()->format("d.m.Y H:i"), $nextRunSuffix);
    }
}
