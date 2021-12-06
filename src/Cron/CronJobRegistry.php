<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Cron;

class CronJobRegistry
{
    /** @var CronJobInterface[]|iterable */
    private iterable $jobs;


    /**
     * @param CronJobInterface[]|iterable $jobs
     */
    public function __construct (iterable $jobs)
    {
        $this->jobs = $jobs;
    }


    /**
     * @return CronJobInterface[]
     */
    public function getAllJobs () : array
    {
        $jobs = [];

        foreach ($this->jobs as $job)
        {
            $jobs[\get_class($job)] = $job;
        }

        \uasort(
            $jobs,
            function (CronJobInterface $left, CronJobInterface $right)
            {
                return \strnatcasecmp($left->getName(), $right->getName());
            }
        );

        return $jobs;
    }


    public function getJobByKey (string $key) : ?CronJobInterface
    {
        foreach ($this->jobs as $job)
        {
            if ($key === \get_class($job))
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
