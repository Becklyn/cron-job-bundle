<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Data;

class CronStatus
{
    /**
     * @var bool
     */
    private $successful;


    /**
     * @var string|null
     */
    private $log;


    /**
     * @param bool        $successful
     * @param string|null $log
     */
    public function __construct (bool $successful, ?string $log = null)
    {
        $this->successful = $successful;
        $this->log = $log;
    }


    /**
     * @return bool
     */
    public function isSuccessful () : bool
    {
        return $this->successful;
    }


    /**
     * @return string|null
     */
    public function getLog () : ?string
    {
        return $this->log;
    }
}
