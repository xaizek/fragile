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

// TODO: maybe accept second argument which would specify builder or branch name
if (sizeof($argv) != 2) {
    print "Usage: ${argv[0]} revision\n";
    die("Wrong invocation\n");
}

$revision = $argv[1];

$sql = 'INSERT INTO buildsets(revision) VALUES(?)';
$statement = DB::prepare($sql);
if (!$statement || $statement->execute([$revision]) === false) {
    die("Failed to schedule buildset\n"
      . print_r(DB::errorInfo(), true));
}

$buildset = DB::lastInsertId();

$builders = [];

if ($handle = opendir(BUILDERS_PATH)) {
    while (($entry = readdir($handle)) !== false) {
        if ($entry != '.' && $entry != '..') {
            schedule_build($entry, $buildset);
            array_push($builders, $entry);
        }
    }

    closedir($handle);
}

print "Buildset ID: $buildset\n";
if (sizeof($builders) == 0) {
    print "No builders were scheduled\n";
} else {
    print "Scheduled " . sizeof($builders) . " builder"
        . (sizeof($builders) == 1 ? '' : 's')
        . ": " . join(', ', $builders) . "\n";
}

function schedule_build($builder_name, $buildset)
{
    $sql = 'INSERT INTO builds(buildset, buildername, output, status) '
         . 'VALUES(?, ?, "", "pending")';
    $statement = DB::prepare($sql);
    if (!$statement ||
        $statement->execute([$buildset, $builder_name] === false) {
        die("Failed to schedule build\n" . print_r(DB::errorInfo(), true));
    }
}

?>
