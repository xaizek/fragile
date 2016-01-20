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

require_once __DIR__ . '/classes/Build.php';
require_once __DIR__ . '/classes/Buildset.php';

// TODO: maybe allow specifying list of builders
if (sizeof($argv) != 3) {
    print "Usage: ${argv[0]} name revision\n";
    die("Wrong invocation\n");
}

$name = $argv[1];
$revision = $argv[2];

$buildset = Buildset::create($name, $revision);

$builders = [];

if ($handle = opendir(BUILDERS_PATH)) {
    while (($entry = readdir($handle)) !== false) {
        if ($entry != '.' && $entry != '..') {
            Build::create($buildset, $entry);
            array_push($builders, $entry);
        }
    }

    closedir($handle);
}

print "Buildset ID: $buildset->buildsetid\n";
if (sizeof($builders) == 0) {
    print "No builders were scheduled\n";
} else {
    print "Scheduled " . sizeof($builders) . " builder"
        . (sizeof($builders) == 1 ? '' : 's')
        . ": " . join(', ', $builders) . "\n";
}

?>
