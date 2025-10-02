<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Data;

class CronStatus
{
    public function __construct(private readonly bool $succeeded, private readonly ?string $log = null)
    {
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
