<?php
// Copyright (C) 2017 xaizek <xaizek@openmailbox.org>
//
// fragile is free software: you can redistribute it and/or modify it under the
// terms of the GNU Affero General Public License as published by the Free
// Software Foundation, version 3.
//
// fragile is distributed in the hope that it will be useful, but WITHOUT ANY
// WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
// details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once __DIR__ . '/classes/Build.php';
require_once __DIR__ . '/classes/Builds.php';
require_once __DIR__ . '/classes/Buildsets.php';

if (!isset($_GET['branch'])) {
    die("Invalid parameters.\n");
}

$branch = $_GET['branch'];

try {
    $buildset = Buildsets::getLastCompletedOf($branch);

    if ($buildset === null) {
        exit(1);
    }

    $builds = Builds::getBuildsForAll([$buildset]);
    $passed = true;
    foreach ($builds as $build) {
        if ($build->status !== 'OK') {
            $passed = false;
            break;
        }
    }

    $label = $passed ? 'passing' : 'failing';
    $color = $passed ? '#4b1' : '#555';

    header('Content-type: image/svg+xml');
    header('Cache-control: no-cache');
    print
    "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"90\" height=\"20\">
        <linearGradient id=\"a\" x2=\"0\" y2=\"100%\">
            <stop offset=\"0\" stop-color=\"#bbb\" stop-opacity=\".1\"/>
            <stop offset=\"1\" stop-opacity=\".1\"/>
        </linearGradient>
        <rect rx=\"3\" width=\"90\" height=\"20\" fill=\"#c4374e\"/>
        <rect rx=\"3\" x=\"37\" width=\"53\" height=\"20\" fill=\"$color\"/>
        <path fill=\"$color\" d=\"M37 0h4v20h-4z\"/>
        <rect rx=\"3\" width=\"90\" height=\"20\" fill=\"url(#a)\"/>
        <g fill=\"#fff\" text-anchor=\"middle\" font-family=\"sans-serif\" font-size=\"11\">
            <text x=\"19.5\" y=\"15\" fill=\"#010101\" fill-opacity=\".3\">build</text>
            <text x=\"19.5\" y=\"14\">build</text>
            <text x=\"62.5\" y=\"15\" fill=\"#010101\" fill-opacity=\".3\">$label</text>
            <text x=\"62.5\" y=\"14\">$label</text>
        </g>
    </svg>";
} catch (PDOException $e) {
    print "Something went wrong\n";
}

?>
