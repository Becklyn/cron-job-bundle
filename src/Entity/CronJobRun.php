<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="cron_job_runs",
 *     indexes={
 *          @ORM\Index(name="job_key", columns={"job_key"})
 *     }
 * )
 */
class CronJobRun
{
    /**
     * @var int|null
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;


    /**
     * @var string
     * @ORM\Column(name="job_key", type="string", length=255)
     */
    private $jobKey;


    /**
     * @var bool
     * @ORM\Column(name="is_successful", type="boolean")
     */
    private $successful;


    /**
     * @var string|null
     * @ORM\Column(name="log", type="text", nullable=true)
     */
    private $log;


    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(name="time_run", type="datetime_immutable")
     */
    private $timeRun;

    /**
     * @var int|null
     * @ORM\Column(name="error_count", type="integer", nullable=true)
     */
    private $errorCount;

    /**
     */
    public function __construct (string $jobKey, bool $successful, ?string $log, ?int $errorCount, \DateTimeImmutable $timeRun)
    {
        $this->jobKey = $jobKey;
        $this->successful = $successful;
        $this->log = $log;
        $this->errorCount = $errorCount;
        $this->timeRun = $timeRun;
    }


    /**
     */
    public function getId () : ?int
    {
        return $this->id;
    }



    /**
     */
    public function getJobKey () : string
    {
        return $this->jobKey;
    }


    /**
     */
    public function isSuccessful () : bool
    {
        return $this->successful;
    }


    /**
     */
    public function getLog () : ?string
    {
        return $this->log;
    }


    /**
     */
    public function getTimeRun () : \DateTimeImmutable
    {
        return $this->timeRun;
    }

    public function getErrorCount(): ?int
    {
        return $this->errorCount;
    }

}
