<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'cron_job_runs')]
#[ORM\Index(columns: ['job_key'], name: 'job_key')]
#[ORM\Entity]
class CronJobRun
{
    //region Fields
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    //endregion


    public function __construct(
        #[ORM\Column(name: 'job_key', type: 'string', length: 255)]
        private readonly string $jobKey,
        #[ORM\Column(name: 'is_successful', type: 'boolean')]
        private readonly bool $successful,
        #[ORM\Column(name: 'log', type: 'text', nullable: true)]
        private readonly ?string $log,
        #[ORM\Column(name: 'time_run', type: 'datetime_immutable')]
        private readonly \DateTimeImmutable $timeRun
    )
    {
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
