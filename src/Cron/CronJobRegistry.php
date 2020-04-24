<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Cron;

use Becklyn\CronJobBundle\Model\CronModel;
use Psr\Log\LoggerInterface;

class CronJobRegistry
{
    /**
     * @var CronJobInterface[]|iterable
     */
    private $jobs;


    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @var CronModel
     */
    private $logModel;


    /**
     * @param CronJobInterface[]|iterable $jobs
     */
    public function __construct (iterable $jobs, LoggerInterface $logger, CronModel $logModel)
    {
        $this->jobs = $jobs;
        $this->logger = $logger;
        $this->logModel = $logModel;
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


    /**
     */
    public function hasJobs () : bool
    {
        return \count($this->jobs) > 0;
    }
}
