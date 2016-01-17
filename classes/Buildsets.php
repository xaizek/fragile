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
        $sql = 'SELECT buildsetid, revision FROM buildsets '
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
                                    $buildsetinfo['revision']));
        }

        return $buildsets;
    }
}

?>
