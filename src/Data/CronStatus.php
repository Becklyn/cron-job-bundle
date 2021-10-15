<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Data;

class CronStatus
{
    /**
     * @var bool
     */
    private $succeeded;


    /**
     * @var string|null
     */
    private $log;

    /**
     * @var int
     */
    private $errorCount;


    /**
     */
    public function __construct (bool $succeeded, ?string $log = null, ?int $errorCount = null)
    {
        $this->succeeded = $succeeded;
        $this->log = $log;
        $this->errorCount = $errorCount;
    }


    /**
     */
    public function isSucceeded () : bool
    {
        return $this->succeeded;
    }


    /**
     */
    public function getLog () : ?string
    {
        return $this->log;
    }

    public function getErrorCount () : ?int
    {
        return $this->errorCount;
    }
}
