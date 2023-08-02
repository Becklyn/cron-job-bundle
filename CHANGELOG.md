2.6.0
=====

*   (improvement) Add support for psr/log ^3.0.0.
*   (internal) Remove support for PHP 7.4.


2.5.0
=====

*   (internal) Fix Symfony deprecations.
*   (internal) Replace TravisCI with GitHub Actions.
*   (improvement) Add missing property types and return types.
*   (internal) Remove support for Symfony 4.4.
*   (improvement) Add support for Symfony 6.


2.4.0
=====

*   (feature) Try to recover faulty `EntityManager` connection in case a Cron crashes unexpectedly to make sure the `CronJobRun` gets persisted, to avoid immediate re-runs.
*   (bug) Pass in entire Cron output to `CronJobRun` when a Cron crashes unexpectedly.
*   (internal) Remove project-specific `phpstan.neon`.


2.3.0
=====

*   (feature) Provide additional meta-information about crashed `CronJobInterface` to Sentry.


2.2.0
====

*   (feature) Implementation of Sentry logging.
*   (bug) Fix `RunCommand` to execute User given jobs via CLI.


2.1.3
====

*   (feature) Users can now select dedicated jobs or single jobs to be executed via CLI (see cron:run -h for avalible commands).
*   (internal) Replace TravisCI with GitHub Actions.
*   (improvement) Bump minimum PHP 7 version to 7.4.
*   (improvement) Add property types and removed unused services/dependencies.


2.1.2
====

*   (feature) Now supports PHP 8.


2.1.1
====

*   (bug) Fix `CronJobBundleConfiguration` namespace.


2.1.0
====

*   (feature) Automatically delete old cron job logs.


2.0.2
=====

*   (improvement) Remove time limit, memory limit and disable profiler when running the cron jobs.


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
