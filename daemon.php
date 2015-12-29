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

require_once 'config.php';
require_once 'db.php';

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

prepare_repository();
serve();

function prepare_repository()
{
    if (!create_path(REPO_PATH)) {
        return;
    }

    system('git clone ' . REPO_URL . ' ' . REPO_PATH, $retval);
    if ($retval != 0) {
        del_tree(REPO_PATH);
        die("Failed to clone repository\n");
    }
}

function create_path($path)
{
    if (is_dir($path)) {
        return false;
    }

    if (!mkdir($path , 0700, true)) {
        die("Failed to create directory: $path\n");
    }
    return true;
}

function del_tree($dir)
{
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? del_tree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function serve()
{
    while (true) {
        run_builds();
        sleep(DAEMON_TIMEOUT);
    }
}

function run_builds()
{
    $sql = 'SELECT buildset, buildername, revision FROM builds, buildsets '
         . 'WHERE status = "pending" AND builds.buildset = buildsets.buildsetid '
         . 'ORDER BY buildset ASC';
    $statement = DB::query($sql);
    if (!$statement) {
        die("Failed to query pending builds\n"
          . print_r(DB::errorInfo(), true));
    }

    $buildsinfo = $statement->fetchAll();

    foreach ($buildsinfo as $buildinfo) {
        // checkout revision
        system('git remote update', $retval);
        if ($retval != 0) {
            die("Failed to update repository\n");
        }
        system('git checkout ' . $buildinfo['revision'], $retval);
        if ($retval != 0) {
            die("Failed to checkout revision\n");
        }

        run_build($buildinfo['buildset'], $buildinfo['buildername']);
    }
}

function run_build($buildset, $buildername)
{
    // TODO: measure and record execution time of the build
    // TODO: record time and date of the build

    $sql = 'UPDATE builds SET status = "running" '
         . 'WHERE buildset = ? AND buildername = ?';
    $statement = DB::prepare($sql);
    if (!$statement ||
        $statement->execute(array($buildset, $buildername)) === false) {
        die("Failed to set build status to 'running'\n"
          . print_r(DB::errorInfo(), true));
    }

    $output = '';

    $build_path = BUILDS_PATH . "/$buildername";
    create_path($build_path);

    $handle = popen("cd $build_path && "
                   . BUILDERS_PATH . '/' . $buildername . ' 2>&1', 'r');
    while (!feof($handle)) {
        $output .= fgets($handle);
        // TODO: append output to database record every N lines (e.g. 100)
    }
    $exitcode = pclose($handle);

    $sql = 'UPDATE builds SET status = ?, output = ? '
         . 'WHERE buildset = ? AND buildername = ?';
    $statement = DB::prepare($sql);
    // TODO: store exitcode in the database
    if (!$statement ||
        $statement->execute(array(($exitcode == 0) ? 'OK' : 'FAIL', $output,
                                  $buildset, $buildername)) === false) {
        die("Failed to set build status to 'running'\n"
          . print_r(DB::errorInfo(), true));
    }
}

?>
