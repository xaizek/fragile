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

if (sizeof($argv) < 3) {
    print "Usage: ${argv[0]} name revision [builder-name..]\n";
    die("Wrong invocation\n");
}

$name = $argv[1];
$revision = $argv[2];

$buildset = Buildset::create($name, $revision);

if (sizeof($argv) > 3) {
    $builders = scheduleBuilders($buildset, BUILDERS_PATH,
                                 array_slice($argv, 3));
} else {
    $builders = scheduleBuildersIn($buildset, BUILDERS_PATH, '');
    $builders = array_merge($builders,
                            scheduleBuildersIn($buildset, BUILDERS_PATH,
                                              "$name/"));
}

print "Buildset ID: $buildset->buildsetid\n";
if (sizeof($builders) == 0) {
    print "No builders were scheduled\n";
} else {
    print "Scheduled " . sizeof($builders) . " builder"
        . (sizeof($builders) == 1 ? '' : 's')
        . ": " . join(', ', $builders) . "\n";
}

/**
 * @brief Schedules builders in @p dir directory specified by their name.
 *
 * @param buildset Parent buildset for newly created builds.
 * @param dir Directory to look for builders.
 * @param names List of builder names (appended to @p dir by one).
 *
 * @returns Array of scheduler builder names.
 */
function scheduleBuilders($buildset, $dir, $names)
{
    $builders = [];
    foreach ($names as $name) {
        $path = "$dir/$name";
        if (!is_dir($path)) {
            Build::create($buildset, $name);
            array_push($builders, $name);

            closedir($handle);
        }
    }
    return $builders;
}

/**
 * @brief Schedules builders discovered in @p dir directory.
 *
 * @param buildset Parent buildset for newly created builds.
 * @param dir Directory to look for builders.
 * @param suffix Additional suffix for builders path (appended to @p dir).
 *
 * @returns Array of scheduler builder names.
 */
function scheduleBuildersIn($buildset, $dir, $suffix)
{
    $builders = [];
    $basePath = "$dir/$suffix";
    if (is_dir($basePath) && $handle = opendir($basePath)) {
        while (($entry = readdir($handle)) !== false) {
            $path = "$basePath/$entry";
            if (!is_dir($path) && $entry != '.' && $entry != '..') {
                $builderName = "$suffix$entry";
                Build::create($buildset, $builderName);
                array_push($builders, $builderName);
            }
        }

        closedir($handle);
    }
    return $builders;
}

?>
