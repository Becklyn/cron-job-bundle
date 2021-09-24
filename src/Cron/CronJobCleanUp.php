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

    /** @var int $logTtl */
    private $logTtl;


    public function __construct
    (
        CronModel $cronModel,
        int $logTtl
    )
    {
        $this->cronModel = $cronModel;
        $this->logTtl = $logTtl;
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
        try {
            $success = $this->cronModel->removeOldLogsByLogTtl($this->logTtl);
        } catch (\Exception $e) {
            $success = false;
        }

        return new CronStatus($success, $io->getBuffer());
    }
}
