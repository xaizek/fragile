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
require_once __DIR__ . '/classes/Buildsets.php';
require_once __DIR__ . '/config.php';

require_once __DIR__ . '/header.php';

try {
    displayDashboard();
} catch (PDOException $e) {
    print "<h3>No Database</h3>\n";
}

require_once __DIR__ . '/footer.php';

/**
 * @brief Displays dashboard of builds <-> buildsets.
 */
function displayDashboard()
{
    $buildsets = Buildsets::getLastN(REVISIONS_LIMIT);
    $builds = Builds::getBuildsForAll($buildsets);

    $builders = [];
    foreach ($builds as $build) {
        $builders[$build->buildername][$build->buildset] = $build;
    }

    if (sizeof($builders) == 0) {
        print "<h3>No Builds</h3>\n";
    } else {
        printBuildTable($buildsets, $builders);
    }
}

/**
 * @brief Prints build table.
 *
 * @param buildsets Array of buildsets to display.
 * @param builders Array of builders (arrays of builds per buildset).
 */
function printBuildTable($buildsets, $builders)
{
    // sort builders by their name
    uksort($builders, "builderCmp");

    // output table header
    print '<table class="dashboard"><tr><td></td>' . "\n";
    foreach ($buildsets as $buildset) {
        $ts = gmdate('Y-m-d H:i:s', $buildset->timestamp) . ' UTC';
        print "<td class='revision' title='$ts'>";
        print '#' . htmlentities($buildset->buildsetid) . ': ';
        print htmlentities($buildset->revision);
        print "<br/><span class='name'>"
            . htmlentities($buildset->name)
            . '</span>';
        print "</td>\n";
    }

    foreach ($builders as $buildername => $builderinfo) {
        print "<tr>\n";
        print '<td>' . htmlentities($buildername) . "</td>\n";
        foreach ($buildsets as $buildset) {
            $buildsetid = $buildset->buildsetid;
            if (array_key_exists($buildsetid, $builderinfo)) {
                $status = $builderinfo[$buildsetid]->status;
            } else {
                $status = '-';
            }

            if (statusHasOutput($status)) {
                // FIXME: might need some kind of escaping here
                $build_url = htmlEntities(WEB_ROOT
                                        . "/builds/$buildsetid/$buildername",
                                          ENT_QUOTES);
                $cell = "<a href='$build_url'>$status</a>";
            } else {
                $cell = $status;
            }

            $class = classFromStatus($status);
            print "<td class='$class'>$cell</td>\n";
        }
        print "</tr>\n";
    }

    print "</table>\n";
}

/**
 * @brief Custom key comparison function.
 *
 * Treats keys with slashes in them as greater than other keys.
 *
 * @param a First key.
 * @param b Second key.
 *
 * @returns Value less than, equal to or greater than zero to indicate order.
 */
function builderCmp($a, $b)
{
    $condA = (strpos($a, '/') !== false);
    $condB = (strpos($b, '/') !== false);
    if ($condA ^ $condB) {
        return $condA ? 1 : -1;
    }
    return strcmp($a, $b);
}

/**
 * @brief Checks whether particular status has associated output.
 *
 * @param status Status string.
 *
 * @returns @c true if so, otherwise @c false.
 */
function statusHasOutput($status)
{
    switch ($status) {
        case 'OK':
        case 'FAIL':
        case 'ERROR':
            return true;

        default:
            return false;
    }
}

/**
 * @brief Retrieves CSS style class that corresponds to a given status.
 *
 * @param status Status string.
 *
 * @returns Name of the style as a string.
 */
function classFromStatus($status)
{
    switch ($status) {
        case '-':       return 'build_absent';
        case 'pending': return 'build_pending';
        case 'running': return 'build_running';
        case 'OK':      return 'build_success';
        case 'FAIL':    return 'build_failure';
        case 'ERROR':   return 'build_error';
    }
}

?>
