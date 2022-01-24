<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\Sentry\Integration;

use function Sentry\configureScope;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\IntegrationInterface;
use Sentry\State\Scope;

class CronJobSentryIntegration implements IntegrationInterface
{
    /**
     * @inheritDoc
     */
    public function setupOnce () : void
    {
        configureScope(
            function (Scope $scope) : void
            {
                $scope->addEventProcessor(
                    function (Event $event, ?EventHint $eventHint = null) : Event
                    {
                        if (null === $eventHint)
                        {
                            return $event;
                        }

                        $cronJobInterface = $eventHint->extra["cronJobInterface"] ?? null;
                        $cronJobName = $eventHint->extra["cronJobName"] ?? null;
                        $cronJobForcedRun = $eventHint->extra["cronJobForcedRun"] ?? null;

                        if (null === $cronJobInterface || null === $cronJobName || null === $cronJobForcedRun)
                        {
                            return $event;
                        }

                        $extra = $event->getExtra();
                        $extra["cron_job_bundle.interface"] = $cronJobInterface;
                        $extra["cron_job_bundle.name"] = $cronJobName;
                        $extra["cron_job_bundle.forced_run"] = $cronJobForcedRun;

                        $event->setExtra($extra);

                        return $event;
                    }
                );
            }
        );
    }
}
