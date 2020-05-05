1.x to 2.0
==========


*   The signature of `CronJobInterface::execute` was changed: you get a `BufferedSymfonyStyle` now instead of a 
    plain `SymfonyStyle`. Upgrade your signature.
    * Only a breaking change for PHP < 7.4
