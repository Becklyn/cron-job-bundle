<?php declare(strict_types=1);

namespace Tests\Becklyn\CronJobBundle;

use Becklyn\CronJobBundle\Console\BufferedSymfonyStyle;
use Becklyn\CronJobBundle\Cron\CronJobInterface;
use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Entity\CronJobRun;

trait JobTestTrait
{
    /**
     * @param string $date
     *
     * @return \DateTimeImmutable
     */
    private function createDateTime (string $date) : \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $date);
    }


    /**
     * Creates a new job
     *
     * @param string $cronTab
     *
     * @return CronJobInterface
     */
    private function createJob (string $cronTab) : CronJobInterface
    {
        return new class ($cronTab) implements CronJobInterface
        {
            /** @var string */
            private $cronTab;


            /**
             */
            public function __construct (string $cronTab)
            {
                $this->cronTab = $cronTab;
            }


            /**
             */
            public function getCronTab () : string
            {
                return $this->cronTab;
            }


            /**
             */
            public function getName () : string
            {
                return "My Job";
            }


            /**
             */
            public function execute (BufferedSymfonyStyle $io) : CronStatus
            {
                return new CronStatus(true);
            }
        };
    }


    /**
     * @param string      $date
     * @param bool        $successful
     * @param string|null $log
     *
     * @return CronJobRun
     */
    private function createCronJobRun (string $date, bool $successful = true, ?string $log = null) : CronJobRun
    {
        return new CronJobRun("test", $successful, $log, $this->createDateTime($date));
    }
}
