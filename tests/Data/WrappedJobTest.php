<?php declare(strict_types=1);

namespace Tests\Becklyn\CronJobBundle\Data;

use Becklyn\CronJobBundle\Data\WrappedJob;
use PHPUnit\Framework\TestCase;
use Tests\Becklyn\CronJobBundle\JobTestTrait;

class WrappedJobTest extends TestCase
{
    use JobTestTrait;

    /**
     *
     */
    public function testBasicGetters () : void
    {
        $wrappedJob = new WrappedJob(
            $this->createJob("@daily"),
            $this->createDateTime("2019-10-09 12:34:45")
        );

        $expectedLast = "2019-10-09 00:00:00";
        $expectedNext = "2019-10-10 00:00:00";

        self::assertSame($expectedLast, $wrappedJob->getSupposedLastRun()->format("Y-m-d H:i:s"));
        self::assertSame($expectedNext, $wrappedJob->getNextRun()->format("Y-m-d H:i:s"));
    }


    /**
     *
     */
    public function testDue () : void
    {
        $wrappedJob = new WrappedJob(
            $this->createJob("@daily"),
            $this->createDateTime("2019-10-09 12:34:45")
        );

        self::assertTrue($wrappedJob->isDue(null));
        self::assertTrue($wrappedJob->isDue($this->createCronJobRun("2019-10-08 00:00:00")));
        self::assertTrue($wrappedJob->isDue($this->createCronJobRun("2019-10-08 23:59:59")));
        self::assertFalse($wrappedJob->isDue($this->createCronJobRun("2019-10-09 00:00:00")));
        self::assertFalse($wrappedJob->isDue($this->createCronJobRun("2019-10-09 01:00:00")));
        self::assertFalse($wrappedJob->isDue($this->createCronJobRun("2019-10-10 00:00:00")));
    }
}
