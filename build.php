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

require_once __DIR__ . '/header.php';

require_once __DIR__ . '/classes/Build.php';
require_once __DIR__ . '/classes/Buildset.php';

if (!isset($_GET['buildset']) || !isset($_GET['buildername'])) {
    die("Invalid parameters.\n");
}

$buildsetid = $_GET['buildset'];
$buildername = $_GET['buildername'];

try {
    if (($build = Build::get($buildsetid, $buildername)) === null) {
        print "<h3>No Such Build</h3>\n";
    } elseif (($buildset = Buildset::get($buildsetid)) === null) {
        print "<h3>No Such Buildset</h3>\n";
    } else {
        print "<table class='buildinfo'><tr>\n";
        print "<td class='title'>Buildset:</td>\n";
        print "<td>#" .  htmlentities($buildset->buildsetid) . "</td>\n";
        print "<td class='title'>Builder:</td>\n";
        print "<td>#" .  htmlentities($buildername) . "</td>\n";
        print "<td class='title'>Revision:</td>\n";
        print "<td>" . htmlentities($buildset->revision).  "</td>\n";
        print "<td class='title'>Ref:</td>\n";
        print "<td>" . htmlentities($buildset->name) . "</td>\n";
        print "<td class='title'>Result:</td>\n";
        print "<td>" . htmlentities($build->status) . "</td>\n";
        print "<td class='title'>Exit code:</td>\n";
        print "<td>" . htmlentities($build->exitcode) . "</td>\n";
        print "</tr></table>\n";

        print "<hr/>\n";

        print "<pre>\n";
        print $build->getOutput();
        print "</pre>\n";
    }
} catch (PDOException $e) {
    print "<h3>No Database</h3>\n";
}

require_once __DIR__ . '/footer.php';

?>
