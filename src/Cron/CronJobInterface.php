<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Cron;

use Becklyn\CronJobBundle\Data\CronStatus;
use Symfony\Component\Console\Style\SymfonyStyle;

interface CronJobInterface
{
    /**
     * Returns the cron tab entry.
     */
    public function getCronTab () : string;


    /**
     * Returns the name of the cron job.
     */
    public function getName () : string;


    /**
     * Runs the command.
     */
    public function execute (SymfonyStyle $io) : CronStatus;
}
