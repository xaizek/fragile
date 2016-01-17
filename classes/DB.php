<?php
// Copyright (C) 2015 xaizek <xaizek@openmailbox.org>
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
        if (!self::$objInstance) {
            self::$objInstance = new PDO(DB_DSN);
            self::$objInstance->setAttribute(PDO::ATTR_ERRMODE,
                                             PDO::ERRMODE_EXCEPTION);
            self::$objInstance->exec(<<<EOS
                CREATE TABLE IF NOT EXISTS buildsets (
                    buildsetid INTEGER,
                    revision TEXT NOT NULL,

                    PRIMARY KEY (buildsetid)
                );

                CREATE TABLE IF NOT EXISTS builds (
                    buildset INTEGER,
                    buildername TEXT NOT NULL,
                    output TEXT NOT NULL,
                    status TEXT NOT NULL,

                    PRIMARY KEY (buildset, buildername),
                    FOREIGN KEY (buildset) REFERENCES buildsets(buildsetid)
                );
EOS
);

        }

        return self::$objInstance;
    }

    /**
     * @brief Singleton instance of the DB classs.
     */
    private static $objInstance;
}

?>
