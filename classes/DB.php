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

require_once __DIR__ . '/../config.php';

/**
 * @brief Wrapper for accessing database.
 */
class DB
{
    /**
     * @brief Creates new database connection if one doesn't exist.
     *
     * Private so noone can create new instance via ` = new DB();`.
     */
    private function __construct() {}

    /**
     * @brief Passes on any static calls onto the singleton PDO instance.
     *
     * @param method Method name.
     * @param args List of arguments.
     *
     * @returns Whatever that PDO method returns.
     */
    final public static function __callStatic($method, $args) {
        $objInstance = self::getInstance();

        return call_user_func_array([$objInstance, $method], $args);
    }

    /**
     * @brief Retrieves existing DB instance or creates initial connection.
     *
     * Also creates required tables if they don't exist.
     *
     * @returns DB instance.
     */
    private static function getInstance() {
        if (self::$objInstance) {
            return self::$objInstance;
        }

        self::$objInstance = new PDO(DB_DSN);
        self::$objInstance->setAttribute(PDO::ATTR_ERRMODE,
                                         PDO::ERRMODE_EXCEPTION);

        $statement = self::$objInstance->query('pragma user_version');
        if (!$statement) {
            die("Failed to query user version from the database\n"
              . print_r(DB::errorInfo(), true));
        }

        $userVersion = $statement->fetch();
        if ($userVersion === false) {
            die("Failed to fetch user version from the database\n"
              . print_r(DB::errorInfo(), true));
        }
        $version = $userVersion[0];
        if ($version == 1) {
            return self::$objInstance;
        }

        switch ($version) {
            case 0:
                self::$objInstance->exec(<<<EOS
                    CREATE TABLE IF NOT EXISTS buildsets (
                        buildsetid INTEGER,
                        name TEXT NOT NULL,
                        revision TEXT NOT NULL,
                        timestamp INTEGER,

                        PRIMARY KEY (buildsetid)
                    );

                    CREATE TABLE IF NOT EXISTS builds (
                        buildset INTEGER,
                        buildername TEXT NOT NULL,
                        output BLOB NOT NULL,
                        status TEXT NOT NULL,
                        exitcode INTEGER,

                        PRIMARY KEY (buildset, buildername),
                        FOREIGN KEY (buildset) REFERENCES buildsets(buildsetid)
                    );

                    ALTER TABLE builds ADD COLUMN starttime INTEGER DEFAULT 0;
                    ALTER TABLE builds ADD COLUMN endtime INTEGER DEFAULT 0;
EOS
);
                // Fall through.
            case 1:
                break;
        }

        self::$objInstance->exec('pragma user_version = 1');

        return self::$objInstance;
    }

    /**
     * @brief Singleton instance of the DB class.
     */
    private static $objInstance;
}

?>
