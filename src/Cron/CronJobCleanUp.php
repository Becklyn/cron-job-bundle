<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Cron;

use Becklyn\CronJobBundle\Console\BufferedSymfonyStyle;
use Becklyn\CronJobBundle\Data\CronStatus;
use Becklyn\CronJobBundle\Model\CronModel;

/**
 * @author Marco Woehr <mw@becklyn.com>
 *
 * @since 2021-09-23
 */
class CronJobCleanUp implements CronJobInterface
{
    public function __construct(private readonly CronModel $cronModel, private readonly int $logTtl)
    {
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
            $this->cronModel->removeOldLogsByLogTtl($this->logTtl);
            return new CronStatus(true, $io->getBuffer());
        } catch (\Exception) {
            return new CronStatus(false, $io->getBuffer());
        }
    }
}
