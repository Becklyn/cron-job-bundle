<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Cron;

class CronJobRegistry
{
    /**
     * @param CronJobInterface[]|iterable $jobs
     */
    public function __construct(private readonly iterable $jobs)
    {
    }


    /**
     * @return CronJobInterface[]
     */
    public function getAllJobs () : array
    {
        $jobs = [];

        foreach ($this->jobs as $job)
        {
            $jobs[$job::class] = $job;
        }

        \uasort(
            $jobs,
            static fn(CronJobInterface $left, CronJobInterface $right) : int => \strnatcasecmp($left->getName(), $right->getName())
        );

        return $jobs;
    }


    public function getJobByKey (string $key) : ?CronJobInterface
    {
        foreach ($this->jobs as $job)
        {
            if ($key === $job::class)
            {
                return $job;
            }
        }

        return null;
    }


    /**
     */
    public function hasJobs () : bool
    {
        return \count($this->jobs) > 0;
    }
}
