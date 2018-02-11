<?php
// Copyright (C) 2018 xaizek <xaizek@posteo.net>
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

/**
 * @brief Helper functions.
 */
class Utils
{
    /**
    * @brief Removes subtree.
    *
    * @param dir Directory path to remove.
    *
    * @returns Result of rmdir().
    */
    public static function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_dir($path) && !is_link($path)) {
                Utils::delTree($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

}

?>
