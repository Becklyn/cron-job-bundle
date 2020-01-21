<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Model;

use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Data\WrappedJob;
use Becklyn\CronJobBundle\Entity\CronJobRun;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CronModel
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;


    /**
     * @var EntityRepository
     */
    private $repository;


    /**
     * @param ManagerRegistry $registry
     */
    public function __construct (ManagerRegistry $registry)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $registry->getManager();
        $this->entityManager = $entityManager;

        /** @var EntityRepository $repository */
        $repository = $entityManager->getRepository(CronJobRun::class);
        $this->repository = $repository;
    }


    /**
     * @param WrappedJob $job
     *
     * @return CronJobRun|null
     */
    public function findLastRun (WrappedJob $job) : ?CronJobRun
    {
        return $this->findMostRecentRuns($job, 1)[0] ?? null;
    }


    /**
     * @param WrappedJob $job
     * @param int        $limit
     *
     * @return CronJobRun[]
     */
    public function findMostRecentRuns (WrappedJob $job, int $limit) : array
    {
        return $this->repository->createQueryBuilder("log")
            ->select("log")
            ->andWhere("log.jobKey = :key")
            ->setParameter("key", $job->getKey())
            ->addOrderBy("log.timeRun", "desc")
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


    /**
     * @param WrappedJob $job
     * @param CronStatus $status
     */
    public function logRun (WrappedJob $job, CronStatus $status) : void
    {
        $run = new CronJobRun(
            $job->getKey(),
            $status->isSucceeded(),
            $status->getLog(),
            $job->getSupposedLastRun()
        );

        $this->entityManager->persist($run);
    }


    /**
     * Returns whether the job is due.
     *
     * @param WrappedJob $job
     *
     * @return bool
     */
    public function isDue (WrappedJob $job) : bool
    {
        return $job->isDue($this->findLastRun($job));
    }


    /**
     * Flushes the database changes.
     */
    public function flush () : void
    {
        $this->entityManager->flush();
    }
}
