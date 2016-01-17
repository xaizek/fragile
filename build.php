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

if (!isset($_GET['buildset']) || !isset($_GET['buildername'])) {
    die("Invalid parameters.\n");
}

$buildset = $_GET['buildset'];
$buildername = $_GET['buildername'];

try {
    if (($build = Build::get($buildset, $buildername)) === null) {
        print "<h3>No Such Build</h3>\n";
    } else {
        // TODO: display label with status and other information at the top
        // TODO: also display navigation links

        print '<pre>';
        print $build->getOutput();
        print '</pre>';
    }
} catch (PDOException $e) {
    print "<h3>No Database</h3>\n";
}

require_once __DIR__ . '/footer.php';

?>
