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

require_once 'header.php';

require_once 'config.php';
require_once 'db.php';

try {
    display_dashboard();
} catch (PDOException $e) {
    print "<h3>No Database</h3>\n";
}

require_once 'footer.php';

function display_dashboard()
{
    $sql = 'SELECT buildsetid, revision FROM buildsets '
         . 'ORDER BY buildsetid DESC LIMIT ' . REVISIONS_LIMIT;
    $statement = DB::query($sql);
    if (!$statement) {
        die("Failed to list buildsets from the database\n"
          . print_r(DB::errorInfo(), true));
    }

    $buildsetsinfo = $statement->fetchAll();

    // fetch all builds for the buildset

    $buildersinfo = [];

    if (sizeof($buildsetsinfo) != 0) {
        $sql = 'SELECT buildset, buildername, status FROM builds WHERE buildset='
             . join(' OR buildset=', array_map('question', $buildsetsinfo));
        $statement = DB::prepare($sql);
        if (!$statement ||
            !$statement->execute(array_map('get_builsetid', $buildsetsinfo))) {
            die("Failed to list builds\n"
              . print_r(DB::errorInfo(), true));
        }

        $buildersinfo = $statement->fetchAll();
        if ($buildersinfo === false) {
            die("Failed to fetch builds\n");
        }
    }

    $table = [];
    foreach ($buildersinfo as $row) {
        $table[$row['buildername']][$row['buildset']] = $row;
    }

    if (sizeof($table) == 0) {
        print "<h3>No Builds</h3>\n";
    } else {
        print_build_table($buildsetsinfo, $table);
    }
}

function question($x)
{
    return '?';
}

function get_builsetid($x)
{
    return $x['buildsetid'];
}

function print_build_table($buildsetsinfo, $table)
{
    // sort builders by their name
    ksort($table);

    // output table header
    print '<table><tr><td></td>' . "\n";
    foreach ($buildsetsinfo as $row) {
        print "<td class='revision'>";
        print '#' . htmlentities($row['buildsetid']) . ': ';
        print htmlentities($row['revision']);
        print "</td>\n";
    }

    // for each builder:
    foreach ($table as $buildername => $builderinfo) {
        print "<tr>\n";
        print '<td>' . htmlentities($buildername) . "</td>\n";
        // for each revision:
        foreach ($buildsetsinfo as $buildsetinfo) {
            // output status

            $buildsetid = $buildsetinfo['buildsetid'];
            if (array_key_exists($buildsetid, $builderinfo)) {
                $status = $builderinfo[$buildsetid]['status'];
            } else {
                $status = '-';
            }

            if (status_has_output($status)) {
                // FIXME: might need some kind of escaping here
                $build_url = htmlEntities(WEB_ROOT
                                        . "/builds/$buildername/$buildsetid",
                                          ENT_QUOTES);
                $cell = "<a href='$build_url'>$status</a>";
            } else {
                $cell = $status;
            }

            $class = class_from_status($status);
            print "<td class='$class'>$cell</td>\n";
        }
        print "</tr>\n";
    }

    print "</table>\n";
}

function status_has_output($status)
{
    switch ($status) {
        case 'OK':
        case 'FAIL':
            return true;

        default:
            return false;
    }
}

function class_from_status($status)
{
    switch ($status) {
        case '-':       return 'build_absent';
        case 'pending': return 'build_pending';
        case 'running': return 'build_running';
        case 'OK':      return 'build_success';
        case 'FAIL':    return 'build_failure';
    }
}

?>
