<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Data;

class CronStatus
{
    private bool $succeeded;
    private ?string $log;


    public function __construct (bool $succeeded, ?string $log = null)
    {
        $this->succeeded = $succeeded;
        $this->log = $log;
    }


    public function isSucceeded () : bool
    {
        return $this->succeeded;
    }


    public function getLog () : ?string
    {
        return $this->log;
    }
}
