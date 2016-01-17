<?php
// Copyright (C) 2015 xaizek <xaizek@openmailbox.org>
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
require_once __DIR__ . '/config.php';

// TODO: maybe mark all "running" builds as failed

// TODO: get rid of hard-coded git commands by using scripts

if (!putenv('FRAGILE_REPO=' . REPO_PATH)) {
    die("Failed to set FRAGILE_REPO environment variable\n");
}
if (!putenv('GIT_WORK_TREE=' . REPO_PATH)) {
    die("Failed to set GIT_WORK_TREE environment variable\n");
}
if (!putenv('GIT_DIR=' . REPO_PATH . '/.git')) {
    die("Failed to set GIT_DIR environment variable\n");
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

    system('git clone ' . REPO_URL . ' ' . REPO_PATH, $retval);
    if ($retval != 0) {
        delTree(REPO_PATH);
        die("Failed to clone repository\n");
    }
}

/**
 * @brief Removes subtree.
 *
 * @param dir Directory path to remove.
 *
 * @returns @Result of rmdir().
 */
function delTree($dir)
{
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

/**
 * @brief Performs infinite loop of running builds.
 */
function serve()
{
    while (true) {
        runBuilds();
        sleep(DAEMON_TIMEOUT);
    }
}

/**
 * @brief Executes all pending builds.
 */
function runBuilds()
{
    $builds = Builds::getPendingBuilds();

    foreach ($builds as $build) {
        // checkout revision
        system('git remote update', $retval);
        if ($retval != 0) {
            $build->setStatus('ERROR');
            print "Failed to update repository\n";
            continue;
        }
        system('git checkout ' . $build->revision, $retval);
        if ($retval != 0) {
            $build->setStatus('ERROR');
            print "Failed to checkout revision\n";
            continue;
        }

        runBuild($build);
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
    // TODO: record time and date of the build

    $build->setStatus('running');

    $output = '';

    $buildPath = BUILDS_PATH . "/$build->buildername";
    createPath($buildPath);

    $handle = popen("cd $buildPath && "
                  . BUILDERS_PATH . '/' . $build->buildername . ' 2>&1', 'r');
    while (!feof($handle)) {
        $output .= fgets($handle);
        // TODO: append output to database record every N lines (e.g. 100)
    }
    $exitcode = pclose($handle);

    // TODO: store exitcode in the database
    $build->setResult(($exitcode == 0) ? 'OK' : 'FAIL', $output);
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
    if (is_dir($path)) {
        return false;
    }

    if (!mkdir($path , 0700, true)) {
        die("Failed to create directory: $path\n");
    }
    return true;
}

?>
