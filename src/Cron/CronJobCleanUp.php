<?php declare(strict_types=1);


namespace Becklyn\CronJobBundle\Cron;

use Becklyn\CronJobBundle\Console\BufferedSymfonyStyle;
use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Model\CronModel;

/**
 * @author Marco Woehr <mw@becklyn.com>
 * @since 2021-09-23
 */
class CronJobCleanUp implements CronJobInterface
{
    /** @var CronModel $cronModel */
    private $cronModel;

    /** @var int $storageDuration */
    private $storageDuration;


    public function __construct
    (
        CronModel $cronModel,
        int $storageDuration
    )
    {
        $this->cronModel = $cronModel;
        $this->storageDuration = $storageDuration;
    }


    /**
     * @inheritdoc
     */
    public function getCronTab () : string
    {
        return "@daily";
    }


    /**
     * @inheritdoc
     */
    public function getName () : string
    {
        return "cron:cleanup:logs";
    }


    /**
     * @inheritdoc
     */
    public function execute (BufferedSymfonyStyle $io) : CronStatus
    {
        $success = $this->cronModel->removeOldLogsByStorageDuration($this->storageDuration);

        return new CronStatus($success, $io->getBuffer());
    }
}
