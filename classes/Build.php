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
     * @param exitcode Exit code of the run.
     * @param revision Revision of the associated buildset.
     */
    public function __construct($buildset, $buildername, $status, $exitcode,
                                $revision = null, $starttime = 0, $endtime = 0)
    {
        $this->buildset = $buildset;
        $this->buildername = $buildername;
        $this->status = $status;
        $this->exitcode = $exitcode;
        $this->revision = $revision;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
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
        $sql = 'SELECT buildset, buildername, status, exitcode, '
             . '       starttime, endtime '
             . 'FROM builds '
             . 'WHERE buildset = ? AND buildername = ?';
        $statement = DB::prepare($sql);
        if (!$statement
            || $statement->execute([$buildset, $buildername]) !== true
            || ($buildinfo = $statement->fetch()) === false) {
            return null;
        }

        return new Build($buildinfo['buildset'],
                         $buildinfo['buildername'],
                         $buildinfo['status'],
                         $buildinfo['exitcode'],
                         null,
                         $buildinfo['starttime'],
                         $buildinfo['endtime']);
    }

    /**
     * @brief Creates new build.
     *
     * @param buildset Existing buildset ID.
     * @param buildername Builder name.
     */
    public static function create($buildset, $buildername)
    {
        $sql = 'INSERT INTO '
             . 'builds(buildset, buildername, output, status, exitcode, '
             . '       starttime, endtime) '
             . 'VALUES(?, ?, "", "pending", -1, 0, 0)';
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

        return gzinflate($buildinfo['output']);
    }

    /**
     * @brief Updates the build to indicate that it has been started.
     */
    public function markAsStarted()
    {
        $sql = 'UPDATE builds SET status = "running", starttime = ? '
             . 'WHERE buildset = ? AND buildername = ?';
        $statement = DB::prepare($sql);
        $this->starttime = time();
        if (!$statement ||
            $statement->execute([$this->starttime, $this->buildset,
                                 $this->buildername]) === false) {
            die("Failed to mark build as started\n"
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
    public function markAsFinished($status, $output, $exitcode)
    {
        $output = gzdeflate($output, 9);
        if ($output === false) {
            die("Failed to compress output.");
        }

        $sql = 'UPDATE builds SET status = ?, endtime = ?, output = ?, '
             . '                  exitcode = ? '
             . 'WHERE buildset = ? AND buildername = ?';
        $statement = DB::prepare($sql);
        $this->endtime = time();
        if (!$statement ||
            $statement->execute([$status, $this->endtime, $output, $exitcode,
                                 $this->buildset,
                                 $this->buildername]) === false) {
            die("Failed to set build status to '$status'\n"
              . print_r(DB::errorInfo(), true));
        }

        $this->status = $status;
    }

    /**
     * @brief Retrieves duration of the build.
     *
     * @returns `-1` for unknown duration, and `>= 0` value otherwise.
     */
    public function getDuration()
    {
        if ($this->endtime == 0 && $this->starttime != 0) {
            // the build must be in progress now
            return time() - $this->starttime;
        }
        return ($this->endtime == 0 || $this->starttime == 0)
             ? -1
             : $this->endtime - $this->starttime;
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
     * @brief Exit code of the run (-1 if builder wasn't run).
     */
    public $exitcode;

    /**
     * @brief Revision string, which might be @c null (depends on construction).
     */
    public $revision;

    /**
     * @brief UNIX timestamp of start of processing.
     */
    private $starttime;

    /**
     * @brief UNIX timestamp of end of processing.
     */
    private $endtime;
}

?>
