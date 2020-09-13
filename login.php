<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

define('NO_SESSION', '1');
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';
// Avoid form login if still connected
if (AmpConfig::get('use_auth') && !filter_has_var(INPUT_GET, 'force_display')) {
    $auth = false;
    if (Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')])) {
        $auth = true;
    } else {
        if (Session::auth_remember()) {
            $auth = true;
        }
    }
    if ($auth) {
        header("Location: " . AmpConfig::get('web_path'));

        return false;
    }
}
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/login.php';

require AmpConfig::get('prefix') . UI::find_template('show_login_form.inc.php');
