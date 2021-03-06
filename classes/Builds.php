<?php
// Copyright (C) 2015 xaizek <xaizek@posteo.net>
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

require_once __DIR__ . '/Build.php';
require_once __DIR__ . '/DB.php';

/**
 * @brief Provides builds utilities.
 */
class Builds
{
    /**
     * @brief Forbids instantiation of this utility class.
     */
    private function __construct() {}

    /**
     * @brief Retrieves all builds for all buildsets specified in @p buildsets.
     *
     * @param buildsets Array of Buildset which builds should be queried for.
     *
     * @returns Array of Build.
     */
    public static function getBuildsForAll($buildsets)
    {
        $sql = 'SELECT buildset, buildername, status, exitcode, '
             . '       starttime, endtime '
             . 'FROM builds '
             . 'WHERE buildset='
             . join(' OR buildset=',
                    array_map('Builds::getPlaceholder', $buildsets));
        return self::getBuilds($sql,
                               array_map('Builds::getBuildsetid', $buildsets));
    }

    /**
     * @brief Retrieves all pending builds.
     *
     * @returns Array of Build.
     */
    public static function getPendingBuilds()
    {
        $sql = 'SELECT buildset, buildername, status, exitcode, revision, '
             . '       starttime, endtime '
             . 'FROM builds, buildsets '
             . 'WHERE status = "pending" '
             . '  AND builds.buildset = buildsets.buildsetid '
             . 'ORDER BY buildset ASC';
        return self::getBuilds($sql, []);
    }

    /**
     * @brief Performs query to obtain builds.
     *
     * @param sql SQL request, possibly with placeholders.
     * @param args Arguments to substitute in placeholders.
     *
     * @returns Array of Build.
     */
    private static function getBuilds($sql, $args)
    {
        $statement = DB::prepare($sql);
        if (!$statement || !$statement->execute($args)) {
            die("Failed to list builds\n" . print_r(DB::errorInfo(), true));
        }

        $buildsinfo = $statement->fetchAll();
        if ($buildsinfo === false) {
            die("Failed to fetch builds\n");
        }

        $builds = [];
        foreach ($buildsinfo as $buildinfo) {
            $revision = array_key_exists('revision', $buildinfo)
                      ? $buildinfo['revision']
                      : null;

            array_push($builds,
                       new Build($buildinfo['buildset'],
                                 $buildinfo['buildername'],
                                 $buildinfo['status'],
                                 $buildinfo['exitcode'],
                                 $revision,
                                 $buildinfo['starttime'],
                                 $buildinfo['endtime']));
        }

        return $builds;
    }

    /**
     * @brief Helper for @c array_map() to produce placeholders.
     *
     * @param x Ignored.
     *
     * @returns Placeholder.
     */
    private static function getPlaceholder($x)
    {
        return '?';
    }

    /**
     * @brief Helper for @c array_map() to extract buildset ID.
     *
     * @param x Buildset object.
     *
     * @returns Buildset ID.
     */
    private static function getBuildsetid($x)
    {
        return $x->buildsetid;
    }

    /**
     * @brief Custom value comparison function for a dictionary of builds.
     *
     * As a primary key uses buildset IDs.
     *
     * As a secondary key treats builder names with slashes in them as greater
     * than other keys.
     *
     * @param a First build.
     * @param b Second build.
     *
     * @returns Value less than, equal to or greater than zero to indicate
     *          order.
     */
    public static function builderCmp($a, $b)
    {
        // buildset IDs are the primary key
        if ($a->buildset != $b->buildset) {
            return $a->buildset - $b->buildset;
        }

        // builder name is the secondary key
        return self::builderNameCmp($a->buildername, $b->buildername);
    }

    /**
     * @brief Custom key comparison function for a dictionary of builds.
     *
     * Treats builder names with slashes in them as greater than other keys.
     *
     * @param a First key.
     * @param b Second key.
     *
     * @returns Value less than, equal to or greater than zero to indicate
     *          order.
     */
    public static function builderNameCmp($a, $b)
    {
        // builder name is the secondary key
        $condA = (strpos($a, '/') !== false);
        $condB = (strpos($b, '/') !== false);
        if ($condA ^ $condB) {
            return $condA ? 1 : -1;
        }
        return strcmp($a, $b);
    }
}

?>
