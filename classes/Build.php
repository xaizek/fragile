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
 * @brief Represents single build.
 */
class Build
{
    /**
     * @brief Constructs a build from specified data.
     *
     * @param buildset Buildset ID.
     * @param buildername Name of builder to use.
     * @param status Status string of the build.
     * @param revision Revision of the associated buildset.
     */
    public function __construct($buildset, $buildername, $status,
                                $revision = null)
    {
        $this->buildset = $buildset;
        $this->buildername = $buildername;
        $this->status = $status;
        $this->revision = $revision;
    }

    /**
     * @brief Retrieves existing build.
     *
     * @param buildset Buildset ID.
     * @param buildername Builder name.
     *
     * @returns Build object or @c null if there is no such build.
     */
    public static function get($buildset, $buildername)
    {
        $sql = 'SELECT buildset, buildername, status FROM builds '
             . 'WHERE buildset = ? AND buildername = ?';
        $statement = DB::prepare($sql);
        if (!$statement
            || $statement->execute([$buildset, $buildername]) !== true
            || ($buildinfo = $statement->fetch()) === false) {
            return null;
        }

        return new Build($buildinfo['buildset'],
                         $buildinfo['buildername'],
                         $buildinfo['status']);
    }

    /**
     * @brief Creates new build.
     *
     * @param buildset Existing buildset ID.
     * @param buildername Builder name.
     */
    public static function create($buildset, $buildername)
    {
        $sql = 'INSERT INTO builds(buildset, buildername, output, status) '
             . 'VALUES(?, ?, "", "pending")';
        $statement = DB::prepare($sql);
        if (!$statement || $statement->execute([$buildset->buildsetid,
                                                $buildername]) === false) {
            die("Failed to schedule build\n" . print_r(DB::errorInfo(), true));
        }
    }

    /**
     * @brief Retrieves build output.
     *
     * It's fetched on demand because of its possibly huge size.
     *
     * @returns Multiline string.
     */
    public function getOutput()
    {
        $sql = 'SELECT output FROM builds '
             . 'WHERE buildset = ? AND buildername = ?';
        $statement = DB::prepare($sql);
        if (!$statement ||
            $statement->execute([$this->buildset,
                                 $this->buildername]) === false) {
            die("Failed to query build output\n"
              . print_r(DB::errorInfo(), true));
        }

        $buildinfo = $statement->fetch();
        if ($buildinfo === false) {
            die("Failed to get build output\n"
              . print_r(DB::errorInfo(), true));
        }

        return $buildinfo['output'];
    }

    /**
     * @brief Updates status of the build.
     *
     * @param status New status string.
     */
    public function setStatus($status)
    {
        $sql = 'UPDATE builds SET status = ? '
             . 'WHERE buildset = ? AND buildername = ?';
        $statement = DB::prepare($sql);
        if (!$statement ||
            $statement->execute([$status, $this->buildset,
                                 $this->buildername]) === false) {
            die("Failed to set build status to 'running'\n"
              . print_r(DB::errorInfo(), true));
        }

        $this->status = $status;
    }

    /**
     * @brief Updates status and output of the build.
     *
     * @param status New status string.
     * @param output Multiline build output.
     */
    public function setResult($status, $output)
    {
        $sql = 'UPDATE builds SET status = ?, output = ? '
             . 'WHERE buildset = ? AND buildername = ?';
        $statement = DB::prepare($sql);
        if (!$statement ||
            $statement->execute([$status, $output, $this->buildset,
                                 $this->buildername]) === false) {
            die("Failed to set build status to 'running'\n"
              . print_r(DB::errorInfo(), true));
        }

        $this->status = $status;
    }

    /**
     * @brief ID of buildset to which this build belongs to.
     */
    public $buildset;

    /**
     * @brief Builder used to perform the build.
     */
    public $buildername;

    /**
     * @brief Status string.
     */
    public $status;

    /**
     * @brief Revision string, which might be @c null (depends on construction).
     */
    public $revision;
}

?>
