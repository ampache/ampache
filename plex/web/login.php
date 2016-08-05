<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define('NO_SESSION', '1');
require_once 'init.php';
require_once '../../lib/login.php';

require_once('header.inc.php');
?>
<p class="error">Ampache authentication required.</p>
<div class="configform">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="field">
            <div class="field_label">Username:</div>
            <div class="field_value"><input type="text" name="username" /></div>
        </div>
        <div class="field">
            <div class="field_label">Password:</div>
            <div class="field_value"><input type="password" name="password" /></div>
        </div>
        <div class="formbuttons">
            <input type="submit" value="Login" />
        </div>
    </form>
</div>
<?php
require_once('footer.inc.php');
?>
