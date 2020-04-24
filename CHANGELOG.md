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
