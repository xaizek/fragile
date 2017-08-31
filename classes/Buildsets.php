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

require_once __DIR__ . '/Buildset.php';
require_once __DIR__ . '/DB.php';

/**
 * @brief Provides buildsets utilities.
 */
class Buildsets
{
    /**
     * @brief Forbids instantiation of this utility class.
     */
    private function __construct() {}

    /**
     * @brief Retrieves at most @p n most recent buildsets.
     *
     * @param n Maximum number of build instances to return.
     *
     * @returns Array of Buildset instances.
     */
    public static function getLastN($n)
    {
        $sql = 'SELECT buildsetid, name, revision, timestamp FROM buildsets '
             . "ORDER BY buildsetid DESC LIMIT $n";
        $statement = DB::query($sql);
        if (!$statement) {
            die("Failed to list buildsets from the database\n"
              . print_r(DB::errorInfo(), true));
        }

        $buildsetsinfo = $statement->fetchAll();
        if ($buildsetsinfo === false) {
            die("Failed to fetch buildsets");
        }

        $buildsets = [];
        foreach ($buildsetsinfo as $buildsetinfo) {
            array_push($buildsets,
                       new Buildset($buildsetinfo['buildsetid'],
                                    $buildsetinfo['name'],
                                    $buildsetinfo['revision'],
                                    $buildsetinfo['timestamp']));
        }

        return $buildsets;
    }

    /**
     * @brief Retrieves last completed (no pending builders) build of a branch.
     *
     * @param name Reference (e.g., branch) name.
     *
     * @returns Latest completed build if present, otherwise @c null.
     */
    public static function getLastCompletedOf($name)
    {
        $sql = 'SELECT buildsetid, name, revision, timestamp FROM buildsets '
             . 'WHERE name = ? AND NOT EXISTS '
             . '( SELECT 1 FROM builds '
             .   'WHERE builds.buildset = buildsets.buildsetid '
             .     'AND status IN ("pending", "running") ) '
             . 'ORDER BY buildsetid DESC LIMIT 1';
        $statement = DB::prepare($sql);
        if (!$statement || !$statement->execute([$name])) {
            die("Failed to query buildset\n" . print_r(DB::errorInfo(), true));
        }

        $buildsetinfo = $statement->fetch();
        if ($buildsetinfo === false) {
            // no build was found, this is not an error
            return null;
        }

        return new Buildset($buildsetinfo['buildsetid'],
                            $buildsetinfo['name'],
                            $buildsetinfo['revision'],
                            $buildsetinfo['timestamp']);
    }
}

?>
