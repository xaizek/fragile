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
     * @param name Symbolic name of the build (e.g. branch name).
     * @param revision Revision to use for the new buildset.
     *
     * @returns Buildset object.
     */
    public static function create($name, $revision)
    {
        $sql = 'INSERT INTO buildsets(name, revision, timestamp) '.
               'VALUES(?, ?, ?)';
        $statement = DB::prepare($sql);
        $now = time();
        if (!$statement ||
            $statement->execute([$name, $revision, $now]) === false) {
            die("Failed to schedule buildset\n"
              . print_r(DB::errorInfo(), true));
        }

        $buildsetid = DB::lastInsertId();

        return new Buildset($buildsetid, $name, $revision, $now);
    }

    /**
     * @brief Retrieves existing buildset.
     *
     * @param buildsetid Buildset ID.
     *
     * @returns Buildset object or @c null if there is no such buildset.
     */
    public static function get($buildsetid)
    {
        $sql = 'SELECT name, revision, timestamp FROM buildsets '.
               'WHERE buildsetid = ?';
        $statement = DB::prepare($sql);
        if (!$statement
            || $statement->execute([$buildsetid]) !== true
            || ($buildsetinfo = $statement->fetch()) === false) {
            return null;
        }

        return new Buildset($buildsetid,
                            $buildsetinfo['name'],
                            $buildsetinfo['revision'],
                            $buildsetinfo['timestamp']);
    }

    /**
     * @brief Constructs buildsets from specified information.
     *
     * @param buildsetid Buildset ID.
     * @param name Symbolic name of the build (e.g. branch name).
     * @param revision Associated VCS revision.
     * @param timestamp When the set was scheduled.
     */
    public function __construct($buildsetid, $name, $revision, $timestamp)
    {
        $this->buildsetid = $buildsetid;
        $this->name = $name;
        $this->revision = $revision;
        $this->timestamp = $timestamp;
    }

    /**
     * @brief Unique buildset ID.
     */
    public $buildsetid;

    /**
     * @brief Symbolic name of the build (e.g. branch name).
     */
    public $name;

    /**
     * @brief VCS revision to use for all builds that belong to the buildset.
     */
    public $revision;

    /**
     * @brief UNIX timestamp of set scheduling time.
     */
    public $timestamp;
}

?>
