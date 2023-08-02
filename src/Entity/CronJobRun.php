<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 *
 * @ORM\Table(
 *     name="cron_job_runs",
 *     indexes={
 *
 *          @ORM\Index(name="job_key", columns={"job_key"})
 *     }
 * )
 */
class CronJobRun
{
    //region Fields
    /**
     * @ORM\Id()
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(name="id", type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="job_key", type="string", length=255)
     */
    private string $jobKey;

    /**
     * @ORM\Column(name="is_successful", type="boolean")
     */
    private bool $successful;

    /**
     * @ORM\Column(name="log", type="text", nullable=true)
     */
    private ?string $log;

    /**
     * @ORM\Column(name="time_run", type="datetime_immutable")
     */
    private \DateTimeImmutable $timeRun;
    //endregion


    public function __construct (string $jobKey, bool $successful, ?string $log, \DateTimeImmutable $timeRun)
    {
        $this->jobKey = $jobKey;
        $this->successful = $successful;
        $this->log = $log;
        $this->timeRun = $timeRun;
    }


    //region Field Accessors
    public function getId () : ?int
    {
        return $this->id;
    }


    public function getJobKey () : string
    {
        return $this->jobKey;
    }


    public function isSuccessful () : bool
    {
        return $this->successful;
    }


    public function getLog () : ?string
    {
        return $this->log;
    }


    public function getTimeRun () : \DateTimeImmutable
    {
        return $this->timeRun;
    }
    //endregion
}
