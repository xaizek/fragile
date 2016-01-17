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

require_once __DIR__ . '/DB.php';

/**
 * @brief Represents single buildset.
 */
class Buildset
{
    /**
     * @brief Creates new buildset.
     *
     * @param revision Revision to use for the new buildset.
     *
     * @returns Buildset object.
     */
    public static function create($revision)
    {
        $sql = 'INSERT INTO buildsets(revision) VALUES(?)';
        $statement = DB::prepare($sql);
        if (!$statement || $statement->execute([$revision]) === false) {
            die("Failed to schedule buildset\n"
              . print_r(DB::errorInfo(), true));
        }

        $buildsetid = DB::lastInsertId();

        return new Buildset($buildsetid, $revision);
    }

    /**
     * @brief Constructs buildsets from specified information.
     *
     * @param buildsetid Buildset ID.
     * @param revision Associated VCS revision.
     */
    public function __construct($buildsetid, $revision)
    {
        $this->buildsetid = $buildsetid;
        $this->revision = $revision;
    }

    /**
     * @brief Unique buildset ID.
     */
    public $buildsetid;

    /**
     * @brief VCS revision to use for all builds that belong to the buildset.
     */
    public $revision;
}

?>
