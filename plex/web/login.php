<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

define('NO_SESSION','1');
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
