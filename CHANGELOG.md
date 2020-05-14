2.0.2
=====

*   (bug) Remove time limit, memory limit and disable profiler when running the cron jobs.


2.0.1
=====

*   (improvement) Catch exception in correct place.
*   (improvement) Prevent cron job run if is in maintenance mode.
*   (improvement) Increase TTL of run lock to 10 min.


2.0.0
=====

*   (bc) Update `CronJobInterface::execute()` to use a `BufferedSymfonyStyle`.
*   (feature) Added `BufferedSymfonyStyle` to be able to pass the CLI output to the cron status log entry.


1.1.3
=====

*   (internal) Update bundle infrastructure + CI.
*   (improvement) Add support for `doctrine-bundle v2+`


1.1.2
=====

*   (improvement) Add support for Symfony 5.
*   (internal) Deprecations in the `CronModel` and `RunCommand` have been fixed.


1.1.1
=====

*   Make used PHP version more stable in `bin/cron` and `bin/run`.


1.1.0
=====

*   Fixed invalid return types in `cron:run`
*   Added a wrapper binary that should be called in the cron job (instead of directly calling the Symfony console).


1.0.1
=====

*   The `cron:run` process is now locked, so that no two `run` processes can run in parallel (for example if one task takes longer than 1min).


1.0.0
=====

Initial Release `\o/`
