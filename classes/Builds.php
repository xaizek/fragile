<?php
// Copyright (C) 2015 xaizek <xaizek@openmailbox.org>
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
        $sql = 'SELECT buildset, buildername, status, exitcode FROM builds '
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
        $sql = 'SELECT buildset, buildername, status, exitcode, revision '
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
                                 $revision));
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
}

?>
