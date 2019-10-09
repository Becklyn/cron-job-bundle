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
     * @param bool        $succeeded
     * @param string|null $log
     */
    public function __construct (bool $succeeded, ?string $log = null)
    {
        $this->succeeded = $succeeded;
        $this->log = $log;
    }


    /**
     * @return bool
     */
    public function isSucceeded () : bool
    {
        return $this->succeeded;
    }


    /**
     * @return string|null
     */
    public function getLog () : ?string
    {
        return $this->log;
    }
}
