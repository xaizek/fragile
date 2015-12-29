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

if (!isset($_GET['buildset']) || !isset($_GET['buildername'])) {
    die("Invalid parameters.\n");
}

try {
    $buildset = $_GET['buildset'];
    $buildername = $_GET['buildername'];
    if (build_exists($buildset, $buildername)) {
        display_build($buildset, $buildername);
    } else {
        print "<h3>No Such Build</h3>\n";
    }
} catch (PDOException $e) {
    print "<h3>No Database</h3>\n";
}

require_once 'footer.php';

function build_exists($buildset, $buildername)
{
    $sql = 'SELECT COUNT(*) FROM builds '
         . 'WHERE buildset = ? AND buildername = ?';
    $statement = DB::prepare($sql);
    return $statement
        && $statement->execute([$buildset, $buildername]) === true
        && $statement->fetchColumn() > 0;
}

function display_build($buildset, $buildername)
{
    // TODO: display label with status and other information at the top
    // TODO: also display navigation links
    $sql = 'SELECT output FROM builds '
         . 'WHERE buildset = ? AND buildername = ?';
    $statement = DB::prepare($sql);
    if (!$statement ||
        $statement->execute([$buildset, $buildername]) === false) {
        die("Failed to query build output\n"
          . print_r(DB::errorInfo(), true));
    }

    $buildinfo = $statement->fetch();
    if ($buildinfo === false) {
        die("Failed to get build output\n"
          . print_r(DB::errorInfo(), true));
    }

    print '<pre>';
    print $buildinfo['output'];
    print '</pre>';
}

?>
