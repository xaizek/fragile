<!-- Copyright (C) 2015 xaizek <xaizek@posteo.net>

fragile is free software: you can redistribute it and/or modify it under the
terms of the GNU Affero General Public License as published by the Free Software
Foundation, version 3.

fragile is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along
with this program.  If not, see <http://www.gnu.org/licenses/>.

-->

<?php

require_once __DIR__ . '/config.php';

print "<html><head>\n";

print '<title>' . PROJECT_NAME . " | fragile</title>\n";

print "<link href='" . WEB_ROOT . "/style.css' rel='stylesheet' "
    . " type='text/css'/>\n";
print "<link href='" . WEB_ROOT . "/favicon.png' rel='shortcut icon' "
    . "type='image/png'/>\n";

print "</head><body>\n";
print "<header>"
    . "<span class='menu'><a href='" . WEB_ROOT . "/'>dashboard</a></span>"
    . "<span class='title'><a href='" . PROJECT_URL . "'>"
      . PROJECT_NAME
    . "</a></span>"
    . "</header>\n";

?>
