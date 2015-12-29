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

require_once 'config.php';

class DB
{
    // Creates new database connection if one doesn't exist.  Private so noone
    // can create new instance via ` = new DB();`.
    private function __construct() {}

    // Private so nobody can clone the instance.
    private function __clone() {}

    // Retrieves existing DB instance or creates initial connection.
    public static function getInstance() {
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

    // Passes on any static calls to this class onto the singleton PDO instance.
    final public static function __callStatic($chrMethod, $arrArguments) {
        $objInstance = self::getInstance();

        return call_user_func_array([$objInstance, $chrMethod], $arrArguments);
    }

    private static $objInstance;
}

?>
