<?php
// Copyright (C) 2015 xaizek <xaizek@posteo.net>
//
// fragile is free software: you can redistribute it and/or modify it under the
// terms of the GNU Affero General Public License as published by the Free
// Software Foundation, version 3.
//
// fragile is distributed in the hope that it will be useful, but WITHOUT ANY
// WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
// A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
// details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once __DIR__ . '/classes/Builds.php';
require_once __DIR__ . '/classes/Buildset.php';
require_once __DIR__ . '/classes/Buildsets.php';
require_once __DIR__ . '/classes/Utils.php';
require_once __DIR__ . '/config.php';

// TODO: maybe mark all "running" builds as failed

// TODO: maybe extract Builders and Builder classes

if (!putenv('FRAGILE_REPO=' . REPO_PATH)) {
    die("Failed to set FRAGILE_REPO environment variable\n");
}

prepareRepository();
serve();

/**
 * @brief Clones repository if it doesn't exist yet.
 */
function prepareRepository()
{
    if (!createPath(REPO_PATH)) {
        return;
    }

    system(__DIR__ . "/vcs/clone '" . REPO_URL . "'", $retval);
    if ($retval != 0) {
        Utils::delTree(REPO_PATH);
        die("Failed to clone repository\n");
    }
}

/**
 * @brief Performs infinite loop of running builds.
 */
function serve()
{
    while (true) {
        $builds = Builds::getPendingBuilds();
        while (empty($builds)) {
            sleep(DAEMON_TIMEOUT);
            $builds = Builds::getPendingBuilds();
        }

        runBuilds($builds);
    }
}

/**
 * @brief Executes all pending builds.
 *
 * @param builds List of builds to run.
 */
function runBuilds($builds)
{
    $bysets = [];
    foreach ($builds as $build) {
        if (!array_key_exists($build->buildset, $bysets)) {
            $bysets[$build->buildset] = [];
        }
        array_push($bysets[$build->buildset], $build);
    }

    $revision = '';
    foreach ($bysets as $builds) {
        $buildset = Buildset::get($build->buildset);

        $prevBuildset = Buildsets::getLastCompletedOf($buildset->name);
        if ($prevBuildset !== null) {
            $prioritized = [];
            foreach (Builds::getBuildsForAll([$prevBuildset]) as $build) {
                if ($build->status !== 'OK') {
                    array_push($prioritized, $build->buildername);
                }
            }

            usort($builds, function ($a, $b) use ($prioritized) {
                $pa = in_array($a->buildername, $prioritized, TRUE) ? 1 : 0;
                $pb = in_array($b->buildername, $prioritized, TRUE) ? 1 : 0;
                if ($pb !== $pa) {
                    return $pb - $pa;
                }
                return Builds::builderCmp($a, $b);
            });
        } else {
            usort($builds, "Builds::builderCmp");
        }

        if (!putenv('FRAGILE_REF=' . $buildset->name)) {
            die("Failed to set FRAGILE_REF environment variable\n");
        }

        foreach ($builds as $build) {
            // checkout revision while not doing anything if we already on it
            if ($build->revision !== $revision) {
                $revision = $build->revision;
                system(__DIR__ . "/vcs/checkout '" . $revision . "'", $retval);
                if ($retval != 0) {
                    $build->markAsFinished('ERROR',
                                           "Failed to checkout revision\n",
                                           $retval);
                    $revision = '';
                    continue;
                }
            }

            runBuild($build);
        }
    }
}

/**
 * @brief Executes a build managing its status and output information.
 *
 * @param build Build to perform.
 */
function runBuild($build)
{
    // TODO: measure and record execution time of the build

    $build->markAsStarted();

    $rawOutput = '';

    $buildPath = BUILDS_PATH . "/$build->buildername";
    createPath($buildPath);

    $handle = popen("cd $buildPath && "
                  . BUILDERS_PATH . '/' . $build->buildername . ' 2>&1', 'r');
    while (!feof($handle)) {
        $rawOutput .= fgets($handle);
        // TODO: append output to database record every N lines (e.g. 100)
    }
    $exitcode = pclose($handle);

    $output = makeReport($rawOutput);
    $build->markAsFinished(($exitcode == 0) ? 'OK' : 'FAIL', $output,
                           $exitcode);
}

/**
 * @brief Creates directory if it doesn't exist.
 *
 * @param path Path to create.
 *
 * @returns @c true if it was created, otherwise @c false is returned.
 */
function createPath($path)
{
    // drop cached information about the path
    clearstatcache(false, $path);

    if (is_dir($path)) {
        return false;
    }

    if (!mkdir($path, 0700, true)) {
        die("Failed to create directory: $path\n");
    }
    return true;
}

/**
 * @brief Formats output to produce build report.
 *
 * Result consists of two parts separated by double newline symbol.  The first
 * parts contains index of errors and warnings, the second one is formatted
 * output.
 *
 * @param rawOutput Output from builder script as is.
 *
 * @returns Multiline build report.
 */
function makeReport($rawOutput)
{
    $errors = [];
    $warnings = [];

    $input = preg_split('/\n/', $rawOutput);
    $output = [];
    $msgnum = 1;
    foreach ($input as $line) {
        $re = '/^(.*)(error|warning|Error|Warning|ERROR|WARNING|ERROR SUMMARY)(:\s+)(.*)$/';
        preg_match($re, $line, $matches);
        if (sizeof($matches) != 0 && $matches[4] != '0') {
            $style = strcasecmp($matches[2], 'warning') == 0
                   ? 'warning' : 'error';
            $label = $matches[2] . ': ' . $matches[4];
        } else {
            $matches = [];
        }

        if (sizeof($matches) == 0) {
            $re = '/^()(Segmentation fault)()()$/';
            preg_match($re, $line, $matches);
            if (sizeof($matches) != 0) {
                $style = 'error';
                $label = $matches[2];
            }
        }

        if (sizeof($matches) == 0) {
            $re = '/^()([^:]+:\d+)(:\s+)(recipe for target \'.*\' failed)$/';
            preg_match($re, $line, $matches);
            if (sizeof($matches) != 0) {
                $style = 'error';
                $label = $matches[4];
            }
        }

        if (sizeof($matches) == 0) {
            array_push($output, htmlentities($line));
            continue;
        }

        $anchor = "<a name='m$msgnum'/>";
        $link = "<a href='#m$msgnum'>" . htmlentities($label) . "</a>";

        if ($style === 'warning') {
            array_push($warnings, $link);
        } else {
            array_push($errors, $link);
        }

        $line = htmlentities($matches[1])
              . "<span class='$style-title'>".htmlentities($matches[2])."</span>"
              . htmlentities($matches[3])
              . "<span class='$style-msg'>".htmlentities($matches[4])."</span>";

        array_push($output, $anchor . $line);

        ++$msgnum;
    }

    $header = '';
    if (sizeof($errors) != 0) {
        $header .= "<span class='errors'>Errors:</span><ol><li>";
        $header .= join("</li><li>", $errors);
        $header .= "</li></ol>\n";
    }
    if (sizeof($warnings) != 0) {
        $header .= "<span class='warnings'>Warnings:</span><ol><li>";
        $header .= join("</li><li>", $warnings);
        $header .= "</li></ol>\n";
    }

    return $header . "\n\n" . join("\n", $output);
}

?>
