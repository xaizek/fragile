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

require_once __DIR__ . '/header.php';

require_once __DIR__ . '/classes/Build.php';
require_once __DIR__ . '/classes/Buildset.php';
require_once __DIR__ . '/classes/Utils.php';

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
        print "<div class='infobar'>\n";
        print "<span class='infocell'>\n";
        print "<span class='infotitle'>Buildset:</span>\n";
        print htmlentities($buildset->buildsetid);
        print "</span>\n";
        print "<span class='infocell'>\n";
        print "<span class='infotitle'>Builder:</span>\n";
        print htmlentities($buildername);
        print "</span>\n";
        print "<span class='infocell'>\n";
        print "<span class='infotitle'>Revision:</span>\n";
        print htmlentities($buildset->revision);
        print "</span>\n";
        print "<span class='infocell'>\n";
        print "<span class='infotitle'>Ref:</span>\n";
        print htmlentities($buildset->name);
        print "</span>\n";
        print "<span class='infocell'>\n";
        print "<span class='infotitle'>Result:</span>\n";
        print htmlentities($build->status);
        print "</span>\n";
        print "<span class='infocell'>\n";
        print "<span class='infotitle'>Exit code:</span>\n";
        print htmlentities($build->exitcode);
        print "</span>\n";
        print "<span class='infocell'>\n";
        print "<span class='infotitle'>Duration:</span>\n";
        print htmlentities(Utils::formatDuration($build->getDuration()));
        print "</span>\n";
        print "</div>\n";

        $rawOutput = $build->getOutput();
        $parts = preg_split('/\n\n/', $rawOutput, 2);

        if (!empty($parts[0])) {
            print "<hr/>\n";
            print "<div class='buildreport'>$parts[0]</div>";
        }
        if (!empty($parts[1])) {
            print "<hr/>\n";
            print "<pre>";
            print $parts[1];
            print "</pre>\n";
        }
    }
} catch (PDOException $e) {
    print "<h3>No Database</h3>\n";
}

require_once __DIR__ . '/footer.php';

?>
