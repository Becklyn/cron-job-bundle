<?php declare(strict_types=1);

// region CLI app
$projectDir = findProjectDir();

if (null === $projectDir)
{
    displayError("No project dir found.");
    exit(1);
}

if (is_file("{$projectDir}/MAINTENANCE"))
{
    displayError("Maintenance Mode active, aborting.");
    exit(2);
}

passthru("{$projectDir}/bin/console cron:run --ansi", $returnVar);
exit($returnVar);
// endregion


//region CLI Lib
/**
 * Writes a single line to the screen
 *
 * @param string $message
 */
function writeln (string $message) : void
{
    echo "{$message}\n";
}


/**
 * Finds the project dir for the symfony project, with the symfony CLI.
 *
 * @return string|null
 */
function findProjectDir () : ?string
{
    $dir = dirname(__DIR__, 2);

    while ("/" !== $dir && !is_file("{$dir}/composer.json") && !is_file("{$dir}/bin/console"))
    {
        $dir = dirname($dir);
    }

    return "/" !== $dir
        ? $dir
        : null;
}


/**
 * Displays an error message
 *
 * @param string $error
 */
function displayError (string $error) : void
{
    writeln("");
    writeln(" ########################");
    writeln("           CRON          ");
    writeln(" ########################");
    writeln("");
    writeln("");
    writeln("Error: {$error}");
}
//endregion
