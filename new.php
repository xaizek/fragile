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

require_once __DIR__ . '/classes/Build.php';
require_once __DIR__ . '/classes/Buildset.php';
require_once __DIR__ . '/classes/Utils.php';
require_once __DIR__ . '/config.php';

if (sizeof($argv) < 3) {
    print "Usage: ${argv[0]} name revision [builder-name..]\n";
    die("Wrong invocation\n");
}

$name = $argv[1];
$revision = $argv[2];
$builders = array_slice($argv, 3);

if (substr($name, 0, strlen('fragile-do/')) === 'fragile-do/') {
    $command = substr($name, strlen('fragile-do/'));
    $pieces = explode('%', $command);
    switch ($pieces[0]) {
        case 'clean':
            // XXX: ideally, this would only schedule the operation for the
            //      daemon; currently, we can change FS in parallel with it
            Utils::delTree(BUILDS_PATH);
            exit("Cleaned ".BUILDS_PATH);
        case 'repeat':
            if (sizeof($pieces) != 2) {
                die('repeat command expects an argument');
            }

            $buildset = Buildset::get($pieces[1]);
            if ($buildset === null) {
                die("repeat command expects a valid buildset id as an ".
                    "argument\ngot: ${pieces[1]}");
            }

            $name = $buildset->name;
            $revision = $buildset->revision;
            $builders = [];

            print "Repeating $name@$revision from #{$pieces[1]}\n";
            break;

        default:
            exit("Unknown command: $command");
    }
}

$buildset = Buildset::create($name, $revision);

if (!empty($builders)) {
    $builders = scheduleBuilders($buildset, BUILDERS_PATH, $builders);
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
        if (!is_dir($path) && is_executable($path)) {
            Build::create($buildset, $name);
            array_push($builders, $name);
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
            if (!is_dir($path) && $entry != '.' && $entry != '..' &&
                is_executable($path)) {
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
