#!/bin/php
<?php

/**
 * AppRecorder
 *
 * Records running and active applications on OS X and saves the result in one or multiple CSV file(s).
 *
 * You can find more information on how to use and install this script in the `README.md` included in this package.
 *
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright 2014 Florian Eckerstorfer
 * @license   http://opensource.org/licenses/MIT The MIT License
 */

define('DEFAULT_INTERVAL', 5);

record(
    /* filename pattern */ $_SERVER['argv'][1],
    /* interval */         true === isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : DEFAULT_INTERVAL
);

/**
 * Executes the given AppleScript and returns the output.
 *
 * @param string $script AppleScript
 *
 * @return string Result of the AppleScript
 */
function execAppleScript($script)
{
    exec(sprintf('osascript -e \'%s\'', $script), $result);

    return implode("\n", $result);
}

/**
 * Returns a list of running applications.
 *
 * @return string[]
 */
function getRunningApps()
{
    return array_map('trim', explode(
        ',',
        execAppleScript('tell application "Finder" to get the name of every process whose visible is true')
    ));
}

/**
 * Returns the name of the active application.
 *
 * @return string
 */
function getActiveApplication()
{
    return execAppleScript('tell application "System Events" to get name of first process whose frontmost is true');
}

/**
 * Returns list of opened and closed applications.
 *
 * @param string[] $curr List of currently running processes.
 * @param string[] $prev List of previously running processes.
 *
 * @return string[][] Array with two elements: "opened" is an array with opened processes, "closed" is an array with
 *                    closed processes.
 */
function getChanges(array $curr, $currActive, array $prev = array(), $prevActive = '')
{
    return [
        'opened'      => array_diff($curr, $prev),
        'closed'      => array_diff($prev, $curr),
        'deactivated' => $currActive !== $prevActive ? [ $prevActive ] : [],
        'activated'   => $currActive !== $prevActive ? [ $currActive ] : []
    ];
}

/**
 * Returns `true` if the changes array contains changes.
 *
 * @param string[][] $changes
 *
 * @return boolean `true` if either the "opened" or the "closed" array contains elements.
 */
function hasChanges(array $changes)
{
    return count($changes['opened']) > 0 ||
        count($changes['closed']) > 0 ||
        count($changes['activated']) > 0 ||
        count($changes['deactivated']) > 0;
}

/**
 * Encapsulates the given string in double quotes.
 *
 * @param string $input Input string.
 *
 * @return string String encapsulated in double quotes.
 */
function encapsulate($input)
{
    return sprintf("%s", $input);
}

/**
 * Creates the file with the given name.
 *
 * @param string $filename
 *
 * @return void
 */
function createFile($filename)
{
    $fh = fopen($filename, 'w');
    if (false === $fh) {
        throw new RuntimeException(sprintf('Could not open file "%s".', $filename));
    }

    fputs($fh, implode(',', array_map('encapsulate', [ 'datetime', 'application', 'action' ]))."\n");

    fclose($fh);
}

/**
 * Saves the array of changes to the file with the given name.
 *
 * @param string     $filename
 * @param string[][] $changes
 *
 * @return void
 */
function saveChanges($filename, array $changes = array())
{
    $fh = fopen($filename, 'a');
    if (false === $fh) {
        throw new RuntimeException(sprintf('Could not open file "%s".', $filename));
    }

    $datetime = date('Y-m-d H:i:s');
    foreach ($changes as $action => $applications) {
        foreach ($applications as $application) {
            fputs($fh, implode(',', array_map('encapsulate', [ $datetime, $application, $action ]))."\n");
        }
    }

    fclose($fh);
}

/**
 * Reads the given file and returns the current status. That is, the applications running and the active application.
 *
 * @param string $filename Filename.
 *
 * @return array An array with two elements: "opened" is an array containing a list of all running applications,
 *               "active" is a string containing the name of the last active application.
 */
function getStatusFromFile($filename)
{
    $fh = fopen($filename, 'r');
    if (false === $fh) {
        throw new RuntimeException(sprintf('Could not open file "%s".', $filename));
    }

    $opened = [];
    $active = '';

    $columns = array_flip(fgetcsv($fh));
    while ($row = fgetcsv($fh)) {
        if ('opened' === $row[$columns['action']] && false === in_array($row[$columns['application']], $opened)) {
            $opened[] = $row[$columns['application']];
        } elseif ('closed' === $row[$columns['action']] && true === in_array($row[$columns['application']], $opened)) {
            $opened = array_filter($opened, function ($value) use ($row, $columns) {
                return false === ($value === $row[$columns['application']]);
            });
        } elseif ('activated' === $row[$columns['action']]) {
            $active = $row[$columns['application']];
        } elseif ('deactivated' === $row[$columns['action']]) {
            $active = '';
        }
    }

    fclose($fh);

    return [ 'opened' => $opened, 'active' => $active ];
}

/**
 * Returns the filename based on the given pattern.
 *
 * @param string $pattern Filename pattern.
 *
 * @return string Filename.
 */
function getFilename($pattern)
{
    return str_replace([ '%Y', '%M', '%D' ], [ date('Y'), date('m'), date('d') ], $pattern);
}

/**
 * Records running applications and stores the results in files based on the given pattern.
 *
 * @param string  $filenamePattern Filename pattern.
 * @param integer $interval        Number of seconds between each recording.
 *
 * @return void
 */
function record($filenamePattern, $interval)
{
    $prev = [];
    $prevActive = '';

    $filename = getFilename($filenamePattern);
    if (true === file_exists($filename)) {
        $status = getStatusFromFile($filename);
        $prev       = $status['opened'];
        $prevActive = $status['active'];
    }

    while (true) {
        $filename = getFilename($filenamePattern);
        if (false === file_exists($filename)) {
            createFile($filename);
        }

        $curr       = getRunningApps();
        $currActive = getActiveApplication();

        $changes = getChanges($curr, $currActive, $prev, $prevActive);
        if (true === hasChanges($changes)) {
            saveChanges($filename, $changes);
        }

        $prev       = $curr;
        $prevActive = $currActive;

        sleep($interval);
    }
}
