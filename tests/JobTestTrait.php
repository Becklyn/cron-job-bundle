<?php declare(strict_types=1);

namespace Tests\Becklyn\CronJobBundle;

use DateTimeImmutable;
use Becklyn\CronJobBundle\Console\BufferedSymfonyStyle;
use Becklyn\CronJobBundle\Cron\CronJobInterface;
use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Entity\CronJobRun;

trait JobTestTrait
{
    private function createDateTime (string $date) : DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $date);
    }


    /**
     * Creates a new job
     */
    private function createJob (string $cronTab) : CronJobInterface
    {
        return new class ($cronTab) implements CronJobInterface
        {
            /**
             */
            public function __construct(private readonly string $cronTab)
            {
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


    private function createCronJobRun (string $date, bool $successful = true, ?string $log = null) : CronJobRun
    {
        return new CronJobRun("test", $successful, $log, $this->createDateTime($date));
    }
}
